<?php
/**
 * A base class for all sapphire objects to inherit from
 *
 * This class provides a number of pattern implementations, as well as methods and fixes to add extra psuedo-static
 * and method functionality to PHP
 *
 * @package sapphire
 * @subpackage core
 */
abstract class Object {
	
	/**
	 * An array of extension names and parameters to be applied to this object upon construction. For example:
	 *
	 * <code>
	 * public static $extensions = array (
	 *   'Hierachy',
	 *   "Version('Stage', 'Live')"
	 * );
	 * </code>
	 *
	 * @var array
	 */
	public static $extensions = null;
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**#@+
	 * @var array
	 */
	
	private static
		$statics          = array(),
		$cached_statics   = array(),
		$extra_statics    = array(),
		$replaced_statics = array();
	
	private static
		$classes_constructed = array(),
		$extra_methods       = array(),
		$built_in_methods    = array();
	
	private static
		$custom_classes = array(),
		$strong_classes = array();
	
	/**#@-*/
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * @var string the class name
	 */
	public $class;
	
	/**
	 * @var array all current extension instances
	 */
	protected $extension_instances = array();
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * An implementation of the factory method, allows you to create an instance of a class
	 *
	 * This method first for strong class overloads (singletons & DB interaction), then custom class overloads. If an
	 * overload is found, an instance of this is returned rather than the original class. To overload a class, use
	 * {@link Object::useCustomClass()}
	 *
	 * @param string $class the class name
	 * @param mixed $arguments,... arguments to pass to the constructor
	 * @return Object
	 */
	public static function create() {
		$args  = func_get_args();
		$class = self::getCustomClass(array_shift($args));
		
		if(version_compare(PHP_VERSION, '5.1.3', '>=')) {
			$reflector = new ReflectionClass($class);
			return $reflector->newInstanceArgs($args);
		} else {
			// we're using a PHP install that doesn't support ReflectionClass->newInstanceArgs()
			
			$args = $args + array_fill(0, 9, null);
			return new $class($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
		}
	}
	
	/**
	 * Similar to {@link Object::create()}, except that classes are only overloaded if you set the $strong parameter to
	 * TRUE when using {@link Object::useCustomClass()}
	 *
	 * @param string $class the class name
	 * @param mixed $arguments,... arguments to pass to the constructor
	 * @return Object
	 */
	public static function strong_create() {
		$args  = func_get_args();
		$class = array_shift($args);
		
		if(isset(self::$strong_classes[$class]) && ClassInfo::exists(self::$strong_classes[$class])) {
			$class = self::$strong_classes[$class];
		}
		
		if(version_compare(PHP_VERSION, '5.1.3', '>=')) {
			$reflector = new ReflectionClass($class);
			return $reflector->newInstanceArgs($args);
		} else {
			$args = $args + array_fill(0, 9, null);
			return new $class($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
		}
	}
	
	/**
	 * This class allows you to overload classes with other classes when they are constructed using the factory method
	 * {@link Object::create()}
	 *
	 * @param string $oldClass the class to replace
	 * @param string $newClass the class to replace it with
	 * @param bool $strong allows you to enforce a certain class replacement under all circumstances. This is used in
	 *        singletons and DB interaction classes
	 */
	public static function useCustomClass($oldClass, $newClass, $strong = false) {
		if($strong) {
			self::$strong_classes[$oldClass] = $newClass;
		} else {
			self::$custom_classes[$oldClass] = $newClass;
		}
	}
	
	/**
	 * If a class has been overloaded, get the class name it has been overloaded with - otherwise return the class name
	 *
	 * @param string $class the class to check
	 * @return string the class that would be created if you called {@link Object::create()} with the class
	 */
	public static function getCustomClass($class) {
		if(isset(self::$strong_classes[$class]) && ClassInfo::exists(self::$strong_classes[$class])) {
			return self::$strong_classes[$class];
		} elseif(isset(self::$custom_classes[$class]) && ClassInfo::exists(self::$custom_classes[$class])) {
			return self::$custom_classes[$class];
		}
		
		return $class;
	}
	
	/**
	 * Get a static variable, taking into account SS's inbuild static caches and pseudo-statics
	 *
	 * This method first checks for any extra values added by {@link Object::add_static_var()}, and attemps to traverse
	 * up the extra static var chain until it reaches the top, or it reaches a replacement static.
	 *
	 * If any extra values are discovered, they are then merged with the default PHP static values, or in some cases
	 * completely replace the default PHP static when you set $replace = true, and do not define extra data on any child
	 * classes
	 *
	 * @param string $class
	 * @param string $name the property name
	 * @param bool $uncached if set to TRUE, force a regeneration of the static cache
	 * @return mixed
	 */
	public static function get_static($class, $name, $uncached = false) {
		if(!isset(self::$cached_statics[$class][$name]) || $uncached) {
			$extra     = $builtIn = $break = $replacedAt = false;
			$ancestry  = array_reverse(ClassInfo::ancestry($class));
			
			// traverse up the class tree and build extra static and stop information
			foreach($ancestry as $ancestor) {
				if(isset(self::$extra_statics[$ancestor][$name])) {
					$toMerge = self::$extra_statics[$ancestor][$name];
					
					if(is_array($toMerge) && is_array($extra)) {
						$extra = array_merge($toMerge, $extra);
					} elseif(!$extra) {
						$extra = $toMerge;
					} else {
						$break = true;
					}
					
					if(isset(self::$replaced_statics[$ancestor][$name])) $replacedAt = $break = $ancestor;
					
					if($break) break;
				}
			}
			
			// check whether to merge in the default value
			if($replacedAt && ($replacedAt == $class || !is_array($extra))) {
				$value = $extra;
			} elseif($replacedAt) {
				// determine whether to merge in lower-class variables
				$ancestorRef     = new ReflectionClass(reset($ancestry));
				$ancestorProps   = $ancestorRef->getStaticProperties();
				$ancestorInbuilt = array_key_exists($name, $ancestorProps) ? $ancestorProps[$name] : null;
				
				$replacedRef     = new ReflectionClass($replacedAt);
				$replacedProps   = $replacedRef->getStaticProperties();
				$replacedInbuilt = array_key_exists($name, $replacedProps) ? $replacedProps[$name] : null;
				
				if($ancestorInbuilt != $replacedInbuilt) {
					$value = is_array($ancestorInbuilt) ? array_merge($ancestorInbuilt, (array) $extra) : $extra;
				} else {
					$value = $extra;
				}
			} else {
				// get a built-in value
				$reflector = new ReflectionClass($class);
				$props     = $reflector->getStaticProperties();
				$inbuilt   = array_key_exists($name, $props) ? $props[$name] : null;
				$value     = isset($extra) && is_array($extra) ? array_merge($extra, (array) $inbuilt) : $inbuilt;
			}
			
			self::$cached_statics[$class][$name] = true;
			self::$statics[$class][$name]        = $value;
		}
		
		return self::$statics[$class][$name];
	}
	
	/**
	 * Set a static variable
	 *
	 * @param string $class
	 * @param string $name the property name to set
	 * @param mixed $value
	 */
	public static function set_static($class, $name, $value) {
		self::$statics[$class][$name]        = $value;
		self::$cached_statics[$class][$name] = true;
	}
	
	/**
	 * Get an uninherited static variable - a variable that is explicity set in this class, and not in the parent class
	 *
	 * @param string $class
	 * @param string $name
	 * @return mixed
	 */
	public static function uninherited_static($class, $name) {
		$inherited = self::get_static($class, $name);
		$parent    = null;
		
		if($parentClass = get_parent_class($class)) {
			$parent = self::get_static($parentClass, $name);
		}
		
		if(is_array($inherited) && is_array($parent)) {
			return array_diff_assoc($inherited, $parent);
		}
		
		return ($inherited != $parent) ? $inherited : null;
	}
	
	/**
	 * Merge in a set of additional static variables
	 *
	 * @param string $class
	 * @param array $properties in a [property name] => [value] format
	 * @param bool $replace replace existing static vars
	 */
	public static function addStaticVars($class, $properties, $replace = false) {
		foreach($properties as $prop => $value) self::add_static_var($class, $prop, $value, $replace);
	}
	
	/**
	 * Add a static variable without replacing it completely if possible, but merging in with both existing PHP statics
	 * and existing psuedo-statics
	 *
	 * @param string $class
	 * @param string $name the static name
	 * @param mixed $value
	 * @param bool $replace completely replace existing static values
	 */
	public static function add_static_var($class, $name, $value, $replace = false) {
		if(is_array($value) && isset(self::$extra_statics[$class][$name]) && !$replace) {
			self::$extra_statics[$class][$name] = array_merge_recursive(self::$extra_statics[$class][$name], $value);
		} else {
			self::$extra_statics[$class][$name] = $value;
		}
		
		if ($replace) {
			self::set_static($class, $name, $value);
			self::$replaced_statics[$class][$name] = true;
		} else {
			self::$cached_statics[$class][$name] = null;
		}
	}
	
	/**
	 * Return TRUE if a class has a specified extension
	 *
	 * @param string $class
	 * @param string $requiredExtension the class name of the extension to check for
	 */
	public static function has_extension($class, $requiredExtension) {
		$requiredExtension = strtolower($requiredExtension);
		
		if($extensions = self::get_static($class, 'extensions')) foreach($extensions as $extension) {
			if(($p = strpos($extension, '(')) !== false) $extension = substr($extension, 0, $p);
			if(strtolower($extension) == $requiredExtension) return true;
		}
		
		return false;
	}
	
	/**
	 * Add an extension to a specific class
	 *
	 * @param string $class
	 * @param string $extension the extension to add to the class
	 */
	public static function add_extension($class, $extension) {
		self::$cached_statics[$class]['extensions'] = null;
		self::add_static_var($class, 'extensions', array($extension));
	}
	
	/**
	 * Remove an extension from a class
	 *
	 * @param string $class
	 * @param string $extension
	 */
	public static function remove_extension($class, $extension) {
		if(self::has_extension($class, $extension)) {
			self::set_static (
				$class,
				'extensions',
				array_diff(self::get_static($class, 'extensions'), array($extension))
			);
		}
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	public function __construct() {
		$this->class = get_class($this);
		
		if($extensionClasses = ClassInfo::ancestry($this->class)) foreach($extensionClasses as $class) {
			if($extensions = self::uninherited_static($class, 'extensions')) foreach($extensions as $extension) {
				$instance = eval("return new $extension;");
				$instance->setOwner($this);
				$this->extension_instances[$instance->class] = $instance;
			}
		}
		
		if(!isset(self::$classes_constructed[$this->class])) {
			$this->defineMethods();
			self::$classes_constructed[$this->class] = true;
		}
	}
	
	/**
	 * Attemps to locate and call a method dynamically added to a class at runtime if a default cannot be located
	 *
	 * You can add extra methods to a class using {@link Extensions}, {@link Object::createMethod()} or
	 * {@link Object::addWrapperMethod()}
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		$method = strtolower($method);
		
		if(isset(self::$extra_methods[$this->class][$method])) {
			$config = self::$extra_methods[$this->class][$method];
			
			switch(true) {
				case isset($config['property']) :
					$obj = $config['index'] !== null ?
						$this->{$config['property']}[$config['index']] :
						$this->{$config['property']};
					
					if($obj) return call_user_func_array(array($obj, $method), $arguments);
					
					if($this->destroyed) {
						throw new Exception (
							"Object->__call(): attempt to call $method on a destroyed $this->class object"
						);
					} else {
						throw new Exception (
							"Object->__call(): $this->class cannot pass control to $config[property]($config[index])." .
							' Perhaps this object was mistakenly destroyed?'
						);
					}
				
				case isset($config['wrap']) :
					array_unshift($arguments, $config['method']);
					return call_user_func_array(array($this, $config['wrap']), $arguments);
				
				case isset($config['function']) :
					return $config['function']($this, $arguments);
				
				default :
					throw new Exception (
						"Object->__call(): extra method $method is invalid on $this->class:" . var_export($config, true)
					);
			}
		} else {
			throw new Exception("Object->__call(): the method '$method' does not exist on '$this->class'");
		}
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
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
		return method_exists($this, $method) || isset(self::$extra_methods[$this->class][strtolower($method)]);
	}
	
	/**
	 * Return the names of all the methods available on this object
	 *
	 * @param bool $custom include methods added dynamically at runtime
	 * @return array
	 */
	public function allMethodNames($custom = false) {
		if(!isset(self::$built_in_methods['_set'][$this->class])) $this->buildMethodList();
		
		if($custom && isset(self::$extra_methods[$this->class])) {
			return array_merge(self::$built_in_methods[$this->class], array_keys(self::$extra_methods[$this->class]));
		} else {
			return self::$built_in_methods[$this->class];
		}
	}
	
	protected function buildMethodList() {
		foreach(get_class_methods($this) as $method) {
			self::$built_in_methods[$this->class][strtolower($method)] = strtolower($method);
		}
		
		self::$built_in_methods['_set'][$this->class] = true;
	}
	
	protected function defineMethods() {
		if($this->extension_instances) foreach(array_keys($this->extension_instances) as $key) {
			$this->addMethodsFrom('extension_instances', $key);
		}
		
		if(isset($_REQUEST['debugmethods']) && isset(self::$built_in_methods[$this->class])) {
			Debug::require_developer_login();
			
			echo '<h2>Methods defined on ' . $this->class . '</h2><ul>';
			foreach(self::$built_in_methods[$this->class] as $method) {
				echo "<li>$method</li>";
			}
			echo '</ul>';
		}
	}
	
	/**
	 * Add all the methods from an object property (which is an {@link Extension}) to this object
	 *
	 * @param string $property the property name
	 * @param string|int $index an index to use if the property is an array
	 */
	protected function addMethodsFrom($property, $index = null) {
		$extension = ($index !== null) ? $this->{$property}[$index] : $this->$property;
		
		if(!$extension) {
			throw new InvalidArgumentException (
				"Object->addMethodsFrom(): could not add methods from {$this->class}->{$property}[$index]"
			);
		}
		
		if(method_exists($extension, 'allMethodNames')) {
			foreach($extension->allMethodNames(true) as $method) {
				self::$extra_methods[$this->class][$method] = array (
					'property' => $property,
					'index'    => $index
				);
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
		self::$extra_methods[$this->class][strtolower($method)] = array (
			'wrap'   => $wrap,
			'method' => $method
		);
	}
	
	/**
	 * Add an extra method using raw PHP code passed as a string
	 *
	 * @param string $method the method name
	 * @param string $code the PHP code - arguments will be in an array called $args, while you can access this object
	 *        by using $obj. Note that you cannot call protected methods, as the method is actually an external function
	 */
	protected function createMethod($method, $code) {
		self::$extra_methods[$this->class][strtolower($method)] = array (
			'function' => create_function('$obj, $args', $code)
		);
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * @see Object::get_static()
	 */
	public function stat($name, $uncached = false) {
		return self::get_static(($this->class ? $this->class : get_class($this)), $name, $uncached);
	}
	
	/**
	 * @see Object::set_static()
	 */
	public function set_stat($name, $value) {
		self::set_static(($this->class ? $this->class : get_class($this)), $name, $value);
	}
	
	/**
	 * @see Object::uninherited_static()
	 */
	public function uninherited($name) {
		return self::uninherited_static(($this->class ? $this->class : get_class($this)), $name);
	}
	
	/**
	 * @deprecated
	 */
	public function set_uninherited() {
		user_error (
			'Object->set_uninherited() is deprecated, please use a custom static on your object', E_USER_WARNING
		);
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * Return true if this object "exists" i.e. has a sensible value
	 *
	 * This method should be overriden in subclasses to provide more context about the classes state. For example, a
	 * {@link DataObject} class could return false when it is deleted from the database
	 *
	 * @return bool
	 */
	public function exists() {
		return true;
	}
	
	/**
	 * @return string this classes parent class
	 */
	public function parentClass() {
		return get_parent_class($this);
	}
	
	/**
	 * Check if this class is an instance of a specific class, or has that class as one of its parents
	 *
	 * @param string $class
	 * @return bool
	 */
	public function is_a($class) {
		return $this instanceof $class;
	}
	
	/**
	 * @return string the class name
	 */
	public function __toString() {
		return $this->class;
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * Calls a method if available on both this object and all applied {@link Extensions}, and then attempts to merge
	 * all results into an array
	 *
	 * @param string $method the method name to call
	 * @param mixed $argument a single argument to pass
	 * @return mixed
	 * @todo integrate inheritance rules
	 */
	public function invokeWithExtensions($method, $argument = null) {
		$result = method_exists($this, $method) ? array($this->$method($argument)) : array();
		$extras = $this->extend($method, $argument);
		
		return $extras ? array_merge($result, $extras) : $result;
	}
	
	/**
	 * Run the given function on all of this object's extensions. Note that this method originally returned void, so if
	 * you wanted to return results, you're hosed
	 *
	 * Currently returns an array, with an index resulting every time the function is called. Only adds returns if
	 * they're not NULL, to avoid bogus results from methods just defined on the parent decorator. This is important for
	 * permission-checks through extend, as they use min() to determine if any of the returns is FALSE. As min() doesn't
	 * do type checking, an included NULL return would fail the permission checks
	 * 
	 * @param string $method the name of the method to call on each extension
	 * @param mixed $a1,... up to 7 arguments to be passed to the method
	 * @return array
	 */
	public function extend($method, &$a1=null, &$a2=null, &$a3=null, &$a4=null, &$a5=null, &$a6=null, &$a7=null) {
		$values = array();
		
		if($this->extension_instances) foreach($this->extension_instances as $instance) {
			if($instance->hasMethod($method)) {
				$value = $instance->$method($a1, $a2, $a3, $a4, $a5, $a6, $a7);
				if($value !== null) $values[] = $value;
			}
		}
		
		return $values;
	}
	
	/**
	 * Get an extension instance attached to this object by name
	 *
	 * @param string $extension
	 * @return Extension
	 */
	public function extInstance($extension) {
		if($this->hasExtension($extension)) return $this->extension_instances[$extension];
	}
	
	/**
	 * Returns TRUE if this object has a specific extension applied
	 *
	 * @param string $extension
	 * @return bool
	 */
	public function hasExtension($extension) {
		return isset($this->extension_instances[$extension]);
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * Cache the results of an instance method in this object to a file, or if it is already cache return the cached
	 * results
	 *
	 * @param string $method the method name to cache
	 * @param int $lifetime the cache lifetime in seconds
	 * @param string $ID custom cache ID to use
	 * @param array $arguments an optional array of arguments
	 * @return mixed the cached data
	 */
	public function cacheToFile($method, $lifetime = 3600, $ID = false, $arguments = array()) {
		if(!$this->hasMethod($method)) {
			throw new InvalidArgumentException("Object->cacheToFile(): the method $method does not exist to cache");
		}
		
		$cacheName = $this->class . '_' . $method;
		
		if(!is_array($arguments)) $arguments = array($arguments);
		
		if($ID) $cacheName .= '_' . $ID;
		if(count($arguments)) $cacheName .= '_' . implode('_', $arguments);
		
		if($data = $this->loadCache($cacheName, $lifetime)) {
			return $data;
		}
		
		$data = call_user_func_array(array($this, $method), $arguments);
		$this->saveCache($cacheName, $data);
		
		return $data;
	}
	
	/**
	 * @deprecated
	 */
	public function cacheToFileWithArgs($callback, $arguments = array(), $lifetime = 3600, $ID = false) {
		user_error (
			'Object->cacheToFileWithArgs() is deprecated, please use Object->cacheToFile() with the $arguments param',
			E_USER_NOTICE
		);
		
		return $this->cacheToFile($callback, $lifetime, $ID, $arguments);
	}
	
	/**
	 * Loads a cache from the filesystem if a valid on is present and within the specified lifetime
	 *
	 * @param string $cache the cache name
	 * @param int $lifetime the lifetime (in seconds) of the cache before it is invalid
	 * @return mixed
	 */
	protected function loadCache($cache, $lifetime = 3600) {
		$path = TEMP_FOLDER . '/' . $this->sanitiseCachename($cache);
		
		if(!isset($_REQUEST['flush']) && file_exists($path) && (filemtime($path) + $lifetime) > time()) {
			return unserialize(file_get_contents($path));
		}
		
		return false;
	}
	
	/**
	 * Save a piece of cached data to the file system
	 *
	 * @param string $cache the cache name
	 * @param mixed $data data to save (must be serializable)
	 */
	protected function saveCache($cache, $data) {
		file_put_contents(TEMP_FOLDER . '/' . $this->sanitiseCachename($cache), serialize($data));
	}
	
	/**
	 * Strip a file name of special characters so it is suitable for use as a cache file name
	 *
	 * @param string $name
	 * @return string the name with all special cahracters replaced with underscores
	 */
	protected function sanitiseCachename($name) {
		return str_replace(array('~', '.', '/', '!', ' ', "\n", "\r", "\t", '\\', ':', '"', '\'', ';'), '_', $name);
	}
	
}
