<?php

namespace SilverStripe\Core;

use BadMethodCallException;
use InvalidArgumentException;
use SilverStripe\Dev\Deprecation;

/**
 * Allows an object to declare a set of custom methods
 */
trait CustomMethods
{

    /**
     * Custom method sources
     *
     * @var array
     */
    protected static $extra_methods = [];

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
    public function __call($method, $arguments)
    {
        // If the method cache was cleared by an an Object::add_extension() / Object::remove_extension()
        // call, then we should rebuild it.
        $class = static::class;
        $config = $this->getExtraMethodConfig($method);
        if (empty($config)) {
            throw new BadMethodCallException(
                "Object->__call(): the method '$method' does not exist on '$class'"
            );
        }

        switch (true) {
            case isset($config['callback']): {
                return $config['callback']($this, $arguments);
            }
            case isset($config['property']) : {
                $property = $config['property'];
                $index = $config['index'];
                $obj = $index !== null ?
                    $this->{$property}[$index] :
                    $this->{$property};

                if (!$obj) {
                    throw new BadMethodCallException(
                        "Object->__call(): {$class} cannot pass control to {$property}({$index})."
                        . ' Perhaps this object was mistakenly destroyed?'
                    );
                }

                // Call without setOwner
                if (empty($config['callSetOwnerFirst'])) {
                    return $obj->$method(...$arguments);
                }

                /** @var Extension $obj */
                try {
                    $obj->setOwner($this);
                    return $obj->$method(...$arguments);
                } finally {
                    $obj->clearOwner();
                }
            }
            case isset($config['wrap']): {
                array_unshift($arguments, $config['method']);
                $wrapped = $config['wrap'];
                return $this->$wrapped(...$arguments);
            }
            case isset($config['function']): {
                return $config['function']($this, $arguments);
            }
            default: {
                throw new BadMethodCallException(
                    "Object->__call(): extra method $method is invalid on $class:"
                    . var_export($config, true)
                );
            }
        }
    }

    /**
     * Adds any methods from {@link Extension} instances attached to this object.
     * All these methods can then be called directly on the instance (transparently
     * mapped through {@link __call()}), or called explicitly through {@link extend()}.
     *
     * @uses addMethodsFrom()
     */
    protected function defineMethods()
    {
        // Define from all registered callbacks
        foreach ($this->extra_method_registers as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Register an callback to invoke that defines extra methods
     *
     * @param string $name
     * @param callable $callback
     */
    protected function registerExtraMethodCallback($name, $callback)
    {
        if (!isset($this->extra_method_registers[$name])) {
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
    public function hasMethod($method)
    {
        return method_exists($this, $method) || $this->getExtraMethodConfig($method);
    }

    /**
     * Get meta-data details on a named method
     *
     * @param string $method
     * @return array List of custom method details, if defined for this method
     */
    protected function getExtraMethodConfig($method)
    {
        // Lazy define methods
        if (!isset(self::$extra_methods[static::class])) {
            $this->defineMethods();
        }

        if (isset(self::$extra_methods[static::class][strtolower($method)])) {
            return self::$extra_methods[static::class][strtolower($method)];
        }
        return null;
    }

    /**
     * Return the names of all the methods available on this object
     *
     * @param bool $custom include methods added dynamically at runtime
     * @return array
     */
    public function allMethodNames($custom = false)
    {
        $class = static::class;
        if (!isset(self::$built_in_methods[$class])) {
            self::$built_in_methods[$class] = array_map('strtolower', get_class_methods($this));
        }

        if ($custom && isset(self::$extra_methods[$class])) {
            return array_merge(self::$built_in_methods[$class], array_keys(self::$extra_methods[$class]));
        } else {
            return self::$built_in_methods[$class];
        }
    }

    /**
     * @param object $extension
     * @return array
     */
    protected function findMethodsFromExtension($extension)
    {
        if (method_exists($extension, 'allMethodNames')) {
            if ($extension instanceof Extension) {
                try {
                    $extension->setOwner($this);
                    $methods = $extension->allMethodNames(true);
                } finally {
                    $extension->clearOwner();
                }
            } else {
                $methods = $extension->allMethodNames(true);
            }
        } else {
            $class = get_class($extension);
            if (!isset(self::$built_in_methods[$class])) {
                self::$built_in_methods[$class] = array_map('strtolower', get_class_methods($extension));
            }
            $methods = self::$built_in_methods[$class];
        }

        return $methods;
    }

    /**
     * Add all the methods from an object property (which is an {@link Extension}) to this object.
     *
     * @param string $property the property name
     * @param string|int $index an index to use if the property is an array
     * @throws InvalidArgumentException
     */
    protected function addMethodsFrom($property, $index = null)
    {
        $class = static::class;
        $extension = ($index !== null) ? $this->{$property}[$index] : $this->$property;

        if (!$extension) {
            throw new InvalidArgumentException(
                "Object->addMethodsFrom(): could not add methods from {$class}->{$property}[$index]"
            );
        }

        $methods = $this->findMethodsFromExtension($extension);
        if ($methods) {
            if ($extension instanceof Extension) {
                Deprecation::notice(
                    '5.0',
                    'Register custom methods from extensions with addCallbackMethod.'
                    . ' callSetOwnerFirst will be removed in 5.0'
                );
            }
            $methodInfo = array(
                'property' => $property,
                'index' => $index,
                'callSetOwnerFirst' => $extension instanceof Extension,
            );

            $newMethods = array_fill_keys($methods, $methodInfo);

            if (isset(self::$extra_methods[$class])) {
                self::$extra_methods[$class] =
                    array_merge(self::$extra_methods[$class], $newMethods);
            } else {
                self::$extra_methods[$class] = $newMethods;
            }
        }
    }

    /**
     * Add all the methods from an object property (which is an {@link Extension}) to this object.
     *
     * @param string $property the property name
     * @param string|int $index an index to use if the property is an array
     */
    protected function removeMethodsFrom($property, $index = null)
    {
        $extension = ($index !== null) ? $this->{$property}[$index] : $this->$property;
        $class = static::class;

        if (!$extension) {
            throw new InvalidArgumentException(
                "Object->removeMethodsFrom(): could not remove methods from {$class}->{$property}[$index]"
            );
        }

        $methods = $this->findMethodsFromExtension($extension);
        if ($methods) {
            foreach ($methods as $method) {
                $methodInfo = self::$extra_methods[$class][$method];

                if ($methodInfo['property'] === $property && $methodInfo['index'] === $index) {
                    unset(self::$extra_methods[$class][$method]);
                }
            }

            if (empty(self::$extra_methods[$class])) {
                unset(self::$extra_methods[$class]);
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
    protected function addWrapperMethod($method, $wrap)
    {
        self::$extra_methods[static::class][strtolower($method)] = array(
            'wrap' => $wrap,
            'method' => $method
        );
    }

    /**
     * Add callback as a method.
     *
     * @param string $method Name of method
     * @param callable $callback Callback to invoke.
     * Note: $this is passed as first parameter to this callback and then $args as array
     */
    protected function addCallbackMethod($method, $callback)
    {
        self::$extra_methods[static::class][strtolower($method)] = [
            'callback' => $callback,
        ];
    }
}
