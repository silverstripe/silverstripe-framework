<?php

namespace SilverStripe\Framework\Core;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Allows an object to declare a set of custom methods
 */
trait CustomMethods {

    /**
     * Custom method sources
     *
     * @var array
     */
	protected static $extra_methods = array();

	/**
	 * Name of methods to invoke by defineMethods for this instance
	 *
	 * @var array
	 */
	protected $extra_method_registers = array();

    /**
     * Non-custom methods
     *
     * @var array
     */
    protected static $built_in_methods = array();

    /**
     * Attempts to locate and call a method dynamically added to a class at runtime if a default cannot be located
     *
     * You can add extra methods to a class using {@link Extensions}, {@link Object::createMethod()} or
     * {@link Object::addWrapperMethod()}
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException
     */
	public function __call($method, $arguments) {
		// If the method cache was cleared by an an Object::add_extension() / Object::remove_extension()
		// call, then we should rebuild it.
        $class = get_class($this);
		if(!array_key_exists($class, self::$extra_methods)) {
			$this->defineMethods();
		}

		// Validate method being invked
		$method = strtolower($method);
		if(!isset(self::$extra_methods[$class][$method])) {
			// Please do not change the exception code number below.
			$class = get_class($this);
			throw new BadMethodCallException("Object->__call(): the method '$method' does not exist on '$class'", 2175);
		}

		$config = self::$extra_methods[$class][$method];

		switch(true) {
			case isset($config['property']) :
				$obj = $config['index'] !== null ?
					$this->{$config['property']}[$config['index']] :
					$this->{$config['property']};

				if($obj) {
					if(!empty($config['callSetOwnerFirst'])) $obj->setOwner($this);
					$retVal = call_user_func_array(array($obj, $method), $arguments);
					if(!empty($config['callSetOwnerFirst'])) $obj->clearOwner();
					return $retVal;
				}

				if(!empty($this->destroyed)) {
					throw new BadMethodCallException(
						"Object->__call(): attempt to call $method on a destroyed $class object"
					);
				} else {
					throw new BadMethodCallException(
						"Object->__call(): $class cannot pass control to $config[property]($config[index])."
							. ' Perhaps this object was mistakenly destroyed?'
					);
				}

			case isset($config['wrap']) :
				array_unshift($arguments, $config['method']);
				return call_user_func_array(array($this, $config['wrap']), $arguments);

			case isset($config['function']) :
				return $config['function']($this, $arguments);

			default :
				throw new BadMethodCallException(
					"Object->__call(): extra method $method is invalid on $class:"
						. var_export($config, true)
				);
		}
	}

    /**
	 * Adds any methods from {@link Extension} instances attached to this object.
	 * All these methods can then be called directly on the instance (transparently
	 * mapped through {@link __call()}), or called explicitly through {@link extend()}.
	 *
	 * @uses addMethodsFrom()
	 */
	protected function defineMethods() {
		$class = get_class($this);

		// Define from all registered callbacks
		foreach($this->extra_method_registers as $callback) {
			call_user_func($callback);
		}
	}

	/**
	 * Register an callback to invoke that defines extra methods
	 *
	 * @param string $name
	 * @param callable $callback
	 */
	protected function registerExtraMethodCallback($name, $callback) {
		if(!isset($this->extra_method_registers[$name])) {
			$this->extra_method_registers[$name] = $callback;
		}
	}

	// --------------------------------------------------------------------------------------------------------------

	/**
	 * Return TRUE if a method exists on this object
	 *
	 * This should be used rather than PHP's inbuild method_exists() as it takes into account methods added via
	 * extensions
	 *
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method) {
		$class = get_class($this);
		return method_exists($this, $method) || isset(self::$extra_methods[$class][strtolower($method)]);
	}

	/**
	 * Return the names of all the methods available on this object
	 *
	 * @param bool $custom include methods added dynamically at runtime
	 * @return array
	 */
	public function allMethodNames($custom = false) {
		$class = get_class($this);
		if(!isset(self::$built_in_methods[$class])) {
			self::$built_in_methods[$class] = array_map('strtolower', get_class_methods($this));
		}

		if($custom && isset(self::$extra_methods[$class])) {
			return array_merge(self::$built_in_methods[$class], array_keys(self::$extra_methods[$class]));
		} else {
			return self::$built_in_methods[$class];
		}
	}



	/**
	 * Add all the methods from an object property (which is an {@link Extension}) to this object.
	 *
	 * @param string $property the property name
	 * @param string|int $index an index to use if the property is an array
	 * @throws InvalidArgumentException
	 */
	protected function addMethodsFrom($property, $index = null) {
		$class = get_class($this);
		$extension = ($index !== null) ? $this->{$property}[$index] : $this->$property;

		if(!$extension) {
			throw new InvalidArgumentException (
				"Object->addMethodsFrom(): could not add methods from {$class}->{$property}[$index]"
			);
		}

		if(method_exists($extension, 'allMethodNames')) {
			$methods = $extension->allMethodNames(true);

		} else {
			if(!isset(self::$built_in_methods[$extension->class])) {
				self::$built_in_methods[$extension->class] = array_map('strtolower', get_class_methods($extension));
			}
			$methods = self::$built_in_methods[$extension->class];
		}

		if($methods) {
			$methodInfo = array(
				'property' => $property,
				'index'    => $index,
				'callSetOwnerFirst' => $extension instanceof \Extension,
			);

			$newMethods = array_fill_keys($methods, $methodInfo);

			if(isset(self::$extra_methods[$class])) {
				self::$extra_methods[$class] =
					array_merge(self::$extra_methods[$class], $newMethods);
			} else {
				self::$extra_methods[$class] = $newMethods;
			}
		}
	}

	/**
	 * Add a wrapper method - a method which points to another method with a different name. For example, Thumbnail(x)
	 * can be wrapped to generateThumbnail(x)
	 *
	 * @param string $method the method name to wrap
	 * @param string $wrap the method name to wrap to
	 */
	protected function addWrapperMethod($method, $wrap) {
		$class = get_class($this);
		self::$extra_methods[$class][strtolower($method)] = array (
			'wrap'   => $wrap,
			'method' => $method
		);
	}

	/**
	 * Add an extra method using raw PHP code passed as a string
	 *
	 * @param string $method the method name
	 * @param string $code the PHP code - arguments will be in an array called $args, while you can access this object
	 *        by using $obj. Note that you cannot call protected methods, as the method is actually an external
	 *        function
	 */
	protected function createMethod($method, $code) {
		$class = get_class($this);
		self::$extra_methods[$class][strtolower($method)] = array (
			'function' => create_function('$obj, $args', $code)
		);
	}
}
