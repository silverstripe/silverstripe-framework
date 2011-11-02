<?php
/**
 * A base class for all sapphire objects to inherit from.
 *
 * This class provides a number of pattern implementations, as well as methods and fixes to add extra psuedo-static
 * and method functionality to PHP.
 * 
 * See {@link Extension} on how to implement a custom multiple
 * inheritance for object instances based on PHP5 method call overloading.
 * 
 * @todo Create instance-specific removeExtension() which removes an extension from $extension_instances,
 * but not from static $extensions, and clears everything added through defineMethods(), mainly $extra_methods.
 *
 * @package sapphire
 * @subpackage core
 */
abstract class Object {
	
	/**
	 * An array of extension names and parameters to be applied to this object upon construction.
	 * 
	 * Example:
	 * <code>
	 * public static $extensions = array (
	 *   'Hierachy',
	 *   "Version('Stage', 'Live')"
	 * );
	 * </code>
	 * 
	 * Use {@link Object::add_extension()} to add extensions without access to the class code,
	 * e.g. to extend core classes.
	 * 
	 * Extensions are instanciated together with the object and stored in {@link $extension_instances}.
	 *
	 * @var array $extensions
	 */
	public static $extensions = null;
	
	/**#@+
	 * @var array
	 */
	
	private static
		$statics = array(),
		$cached_statics = array(),
		$uninherited_statics = array(),
		$cached_uninherited_statics = array(),
		$extra_statics = array(),
		$replaced_statics = array(),
		$_cache_statics_prepared = array();
	
	private static
		$classes_constructed = array(),
		$extra_methods       = array(),
		$built_in_methods    = array();
	
	private static
		$custom_classes = array(),
		$strong_classes = array();
	
	/**#@-*/
	
	/**
	 * @var string the class name
	 */
	public $class;
	
	/**
	 * @var array all current extension instances.
	 */
	protected $extension_instances = array();
	
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
	
	private static $_cache_inst_args = array();
	
	/**
	 * Create an object from a string representation.  It treats it as a PHP constructor without the
	 * 'new' keyword.  It also manages to construct the object without the use of eval().
	 * 
	 * Construction itself is done with Object::create(), so that Object::useCustomClass() calls
	 * are respected.
	 * 
	 * `Object::create_from_string("Versioned('Stage','Live')")` will return the result of
	 * `Object::create('Versioned', 'Stage', 'Live);`
	 * 
	 * It is designed for simple, clonable objects.  The first time this method is called for a given
	 * string it is cached, and clones of that object are returned.
	 * 
	 * If you pass the $firstArg argument, this will be prepended to the constructor arguments. It's
	 * impossible to pass null as the firstArg argument.
	 * 
	 * `Object::create_from_string("Varchar(50)", "MyField")` will return the result of
	 * `Object::create('Vachar', 'MyField', '50');`
	 * 
	 * Arguments are always strings, although this is a quirk of the current implementation rather
	 * than something that can be relied upon.
	 */
	static function create_from_string($classSpec, $firstArg = null) {
		if(!isset(self::$_cache_inst_args[$classSpec.$firstArg])) {
			// an $extension value can contain parameters as a string,
			// e.g. "Versioned('Stage','Live')"
			if(strpos($classSpec,'(') === false) {
				if($firstArg === null) self::$_cache_inst_args[$classSpec.$firstArg] = Object::create($classSpec);
				else self::$_cache_inst_args[$classSpec.$firstArg] = Object::create($classSpec, $firstArg);
								
			} else {
				list($class, $args) = self::parse_class_spec($classSpec);
				
				if($firstArg !== null) array_unshift($args, $firstArg);
				array_unshift($args, $class);
				
				self::$_cache_inst_args[$classSpec.$firstArg] = call_user_func_array(array('Object','create'), $args);
			}
		}
		
		return clone self::$_cache_inst_args[$classSpec.$firstArg];
	}
	
	/**
	 * Parses a class-spec, such as "Versioned('Stage','Live')", as passed to create_from_string().
	 * Returns a 2-elemnent array, with classname and arguments
	 */
	static function parse_class_spec($classSpec) {
		$tokens = token_get_all("<?php $classSpec");
		$class = null;
		$args = array();
		$passedBracket = false;
		
		// Keep track of the current bucket that we're putting data into
		$bucket = &$args;
		$bucketStack = array();
		
		foreach($tokens as $token) {
			$tName = is_array($token) ? $token[0] : $token;
			// Get the class naem
			if($class == null && is_array($token) && $token[0] == T_STRING) {
				$class = $token[1];
			// Get arguments
			} else if(is_array($token)) {
				switch($token[0]) {
				case T_CONSTANT_ENCAPSED_STRING:
					$argString = $token[1];
					switch($argString[0]) {
						case '"': $argString = stripcslashes(substr($argString,1,-1)); break;
						case "'": $argString = str_replace(array("\\\\", "\\'"),array("\\", "'"), substr($argString,1,-1)); break;
						default: throw new Exception("Bad T_CONSTANT_ENCAPSED_STRING arg $argString");
					}
					$bucket[] = $argString;
					break;
			
				case T_DNUMBER:
					$bucket[] = (double)$token[1];
					break;

				case T_LNUMBER:
					$bucket[] = (int)$token[1];
					break;
			
				case T_STRING:
					switch($token[1]) {
						case 'true': $args[] = true; break;
						case 'false': $args[] = false; break;
						default: throw new Exception("Bad T_STRING arg '{$token[1]}'");
					}
				
				case T_ARRAY:
					// Add an empty array to the bucket
					$bucket[] = array();
					$bucketStack[] = &$bucket;
					$bucket = &$bucket[sizeof($bucket)-1];

				}

			} else {
				if($tName == ')') {
					// Pop-by-reference
					$bucket = &$bucketStack[sizeof($bucketStack)-1];
					array_pop($bucketStack);
				}
			}
		}
	
		return array($class, $args);
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
	 * Note that from SilverStripe 2.3.2, Object::get_static() can only be used to get public
	 * static variables, not protected ones.
	 *
	 * @param string $class
	 * @param string $name the property name
	 * @param bool $uncached if set to TRUE, force a regeneration of the static cache
	 * @return mixed
	 */
	public static function get_static($class, $name, $uncached = false) {
		if(!isset(self::$_cache_statics_prepared[$class])) {
			Object::prepare_statics($class);
		}

		if(!isset(self::$cached_statics[$class][$name]) || $uncached) {
			//if($class == 'DataObjectDecoratorTest_MyObject') Debug::message("$class - $name");
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
		if(!isset(self::$_cache_statics_prepared[$class])) {
			Object::prepare_statics($class);
		}

		self::$statics[$class][$name] = $value;
		self::$uninherited_statics[$class][$name] = $value;
		self::$cached_statics[$class][$name] = true;
		self::$cached_uninherited_statics[$class][$name] = true;
	}

	/**
	 * Get an uninherited static variable - a variable that is explicity set in this class, and not in the parent class.
	 * 
	 * Note that from SilverStripe 2.3.2, Object::uninherited_static() can only be used to get public
	 * static variables, not protected ones.
	 * 
	 * @todo Recursively filter out parent statics, currently only inspects the parent class
	 *
	 * @param string $class
	 * @param string $name
	 * @return mixed
	 */
	public static function uninherited_static($class, $name, $uncached = false) {
		if(!isset(self::$_cache_statics_prepared[$class])) {
			Object::prepare_statics($class);
		}
		
		if(!isset(self::$cached_uninherited_statics[$class][$name]) || $uncached) {
			$classRef = new ReflectionClass($class);
			$classProp = $classRef->getStaticPropertyValue($name, null);

			$parentClass = get_parent_class($class);
			if($parentClass) {
				$parentRef = new ReflectionClass($parentClass);
				$parentProp = $parentRef->getStaticPropertyValue($name, null);
				if($parentProp == $classProp) $classProp = null;
			}
			
			// Add data from extra_statics if it has been applied to this specific class (it
			// wouldn't make sense to have them inherit in this method).  This is kept separate
			// from the equivalent get_static code because it's so much simpler
			if(isset(self::$extra_statics[$class][$name])) {
				$toMerge = self::$extra_statics[$class][$name];
				
				if(is_array($toMerge) && is_array($classProp)) {
					$classProp = array_merge($toMerge, $classProp);
				} elseif(!$classProp) {
					$classProp = $toMerge;
				}
			}
			
			self::$cached_uninherited_statics[$class][$name] = true;
			self::$uninherited_statics[$class][$name] = $classProp;
		}

		return self::$uninherited_statics[$class][$name];
	}
	
	/**
	 * Traverse down a class ancestry and attempt to merge all the uninherited static values for a particular static
	 * into a single variable
	 *
	 * @param string $class
	 * @param string $name the static name
	 * @param string $ceiling an optional parent class name to begin merging statics down from, rather than traversing
	 *        the entire hierarchy
	 * @return mixed
	 */
	public static function combined_static($class, $name, $ceiling = false) {
		$ancestry = ClassInfo::ancestry($class);
		$values   = null;
		
		if($ceiling) while(current($ancestry) != $ceiling && $ancestry) {
			array_shift($ancestry);
		}
		
		if($ancestry) foreach($ancestry as $ancestor) {
			$merge = self::uninherited_static($ancestor, $name);
			
			if(is_array($values) && is_array($merge)) {
				$values = array_merge($values, $merge);
			} elseif($merge) {
				$values = $merge;
			}
		}
		
		return $values;
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
	 * and existing psuedo-statics. Uses PHP's array_merge_recursive() with if the $replace argument is FALSE.
	 * 
	 * Documentation from http://php.net/array_merge_recursive:
	 * If the input arrays have the same string keys, then the values for these keys are merged together 
	 * into an array, and this is done recursively, so that if one of the values is an array itself, 
	 * the function will merge it with a corresponding entry in another array too. 
	 * If, however, the arrays have the same numeric key, the later value will not overwrite the original value, 
	 * but will be appended. 
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
			
		// Clear caches
		} else {
			self::$cached_statics[$class][$name] = null;
			self::$cached_uninherited_statics[$class][$name] = null;
		}
	}
	
	/**
	 * Return TRUE if a class has a specified extension
	 *
	 * @param string $class
	 * @param string $requiredExtension the class name of the extension to check for.
	 */
	public static function has_extension($class, $requiredExtension) {
		$requiredExtension = strtolower($requiredExtension);
		if($extensions = self::combined_static($class, 'extensions')) foreach($extensions as $extension) {
			$left = strtolower(Extension::get_classname_without_arguments($extension));
			$right = strtolower(Extension::get_classname_without_arguments($requiredExtension));
			if($left == $right) return true;
		}
		
		return false;
	}
	
	/**
	 * Add an extension to a specific class.
	 * As an alternative, extensions can be added to a specific class
	 * directly in the {@link Object::$extensions} array.
	 * See {@link SiteTree::$extensions} for examples.
	 * Keep in mind that the extension will only be applied to new
	 * instances, not existing ones (including all instances created through {@link singleton()}).
	 *
	 * @param string $class Class that should be decorated - has to be a subclass of {@link Object}
	 * @param string $extension Subclass of {@link Extension} with optional parameters 
	 *  as a string, e.g. "Versioned" or "Translatable('Param')"
	 */
	public static function add_extension($class, $extension) {
		if(!preg_match('/^([^(]*)/', $extension, $matches)) {
			return false;
		}
		$extensionClass = $matches[1];
		if(!class_exists($extensionClass)) {
			user_error(sprintf('Object::add_extension() - Can\'t find extension class for "%s"', $extensionClass), E_USER_ERROR);
		}
		
		if(!ClassInfo::is_subclass_of($extensionClass, 'Extension')) {
			user_error(sprintf('Object::add_extension() - Extension "%s" is not a subclass of Extension', $extensionClass), E_USER_ERROR);
		}
		
		// unset some caches
		self::$cached_statics[$class]['extensions'] = null;
		$subclasses = ClassInfo::subclassesFor($class);
		$subclasses[] = $class;
		if($subclasses) foreach($subclasses as $subclass) {
			unset(self::$classes_constructed[$subclass]);
			unset(self::$extra_methods[$subclass]);
		}
		
		// merge with existing static vars
		$extensions = self::uninherited_static($class, 'extensions');
		
		// We use unshift rather than push so that module extensions are added before built-in ones.
		// in particular, this ensures that the Versioned rewriting is done last.
		if($extensions) array_unshift($extensions, $extension);
		else $extensions = array($extension);
		
		self::set_static($class, 'extensions', $extensions);
		
		// load statics now for DataObject classes
		if(ClassInfo::is_subclass_of($class, 'DataObject')) {
			DataObjectDecorator::load_extra_statics($class, $extension);
		}
	}

	/**
	 * Prepare static variables before processing a {@link get_static} or {@link set_static}
	 * call.
	 */
	private static function prepare_statics($class) {
		// _cache_statics_prepared setting must come first to prevent infinite loops when we call
		// get_static below
		self::$_cache_statics_prepared[$class] = true;

		// load statics now for DataObject classes
		if(is_subclass_of($class, 'DataObject')) {
			$extensions = Object::uninherited_static($class, 'extensions');
			if($extensions) foreach($extensions as $extension) {
				DataObjectDecorator::load_extra_statics($class, $extension);
			}
		}
	}
	
	
	/**
	 * Remove an extension from a class.
	 * Keep in mind that this won't revert any datamodel additions
	 * of the extension at runtime, unless its used before the
	 * schema building kicks in (in your _config.php).
	 * Doesn't remove the extension from any {@link Object}
	 * instances which are already created, but will have an
	 * effect on new extensions.
	 * Clears any previously created singletons through {@link singleton()}
	 * to avoid side-effects from stale extension information.
	 * 
	 * @todo Add support for removing extensions with parameters
	 *
	 * @param string $class
	 * @param string $extension Classname of an {@link Extension} subclass, without parameters
	 */
	public static function remove_extension($class, $extension) {
		if(self::has_extension($class, $extension)) {
			self::set_static(
				$class,
				'extensions',
				array_diff(self::uninherited_static($class, 'extensions'), array($extension))
			);
		}
		
		// unset singletons to avoid side-effects
		global $_SINGLETONS;
		$_SINGLETONS = array();

		// unset some caches
		self::$cached_statics[$class]['extensions'] = null;
		$subclasses = ClassInfo::subclassesFor($class);
		$subclasses[] = $class;
		if($subclasses) foreach($subclasses as $subclass) {
			unset(self::$classes_constructed[$subclass]);
			unset(self::$extra_methods[$subclass]);
		}
		
	}
	
	/**
	 * @param string $class
	 * @param bool $includeArgumentString Include the argument string in the return array,
	 *  FALSE would return array("Versioned"), TRUE returns array("Versioned('Stage','Live')").
	 * @return array Numeric array of either {@link DataObjectDecorator} classnames,
	 *  or eval'ed classname strings with constructor arguments.
	 */
	function get_extensions($class, $includeArgumentString = false) {
		$extensions = self::get_static($class, 'extensions');
		if($includeArgumentString) {
			return $extensions;
		} else {
			$extensionClassnames = array();
			if($extensions) foreach($extensions as $extension) {
				$extensionClassnames[] = Extension::get_classname_without_arguments($extension);
			}
			return $extensionClassnames;
		}
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	public function __construct() {
		$this->class = get_class($this);
		
		// Don't bother checking some classes that should never be extended
		static $notExtendable = array('Object', 'ViewableData', 'RequestHandler');
		
		if($extensionClasses = ClassInfo::ancestry($this->class)) foreach($extensionClasses as $class) {
			if(in_array($class, $notExtendable)) continue;
			
			if($extensions = self::uninherited_static($class, 'extensions')) {
				foreach($extensions as $extension) {
					$instance = self::create_from_string($extension);
					$instance->setOwner(null, $class);
					$this->extension_instances[$instance->class] = $instance;
				}
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
		// If the method cache was cleared by an an Object::add_extension() / Object::remove_extension()
		// call, then we should rebuild it.
		if(empty(self::$cached_statics[get_class($this)])) {
			$this->defineMethods();
		}
		
		$method = strtolower($method);
		
		if(isset(self::$extra_methods[$this->class][$method])) {
			$config = self::$extra_methods[$this->class][$method];
			
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
			// Please do not change the exception code number below.
			
			throw new Exception("Object->__call(): the method '$method' does not exist on '$this->class'", 2175);
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
		if(!isset(self::$built_in_methods[$this->class])) {
			self::$built_in_methods[$this->class] = array_map('strtolower', get_class_methods($this));
		}
		
		if($custom && isset(self::$extra_methods[$this->class])) {
			return array_merge(self::$built_in_methods[$this->class], array_keys(self::$extra_methods[$this->class]));
		} else {
			return self::$built_in_methods[$this->class];
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
	 * Add all the methods from an object property (which is an {@link Extension}) to this object.
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
				'callSetOwnerFirst' => $extension instanceof Extension,
			);

			$newMethods = array_fill_keys($methods, $methodInfo);
			
			if(isset(self::$extra_methods[$this->class])) {
				self::$extra_methods[$this->class] =
					array_merge(self::$extra_methods[$this->class], $newMethods);
			} else {
				self::$extra_methods[$this->class] = $newMethods;
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
	 * do type checking, an included NULL return would fail the permission checks.
	 * 
	 * The extension methods are defined during {@link __construct()} in {@link defineMethods()}.
	 * 
	 * @param string $method the name of the method to call on each extension
	 * @param mixed $a1,... up to 7 arguments to be passed to the method
	 * @return array
	 */
	public function extend($method, &$a1=null, &$a2=null, &$a3=null, &$a4=null, &$a5=null, &$a6=null, &$a7=null) {
		$values = array();
		
		if($this->extension_instances) foreach($this->extension_instances as $instance) {
			if(method_exists($instance, $method)) {
				$instance->setOwner($this);
				$value = $instance->$method($a1, $a2, $a3, $a4, $a5, $a6, $a7);
				if($value !== null) $values[] = $value;
				$instance->clearOwner();
			}
		}
		
		return $values;
	}
	
	/**
	 * Get an extension instance attached to this object by name.
	 * 
	 * @uses hasExtension()
	 *
	 * @param string $extension
	 * @return Extension
	 */
	public function getExtensionInstance($extension) {
		if($this->hasExtension($extension)) return $this->extension_instances[$extension];
	}
	
	/**
	 * Returns TRUE if this object instance has a specific extension applied
	 * in {@link $extension_instances}. Extension instances are initialized
	 * at constructor time, meaning if you use {@link add_extension()}
	 * afterwards, the added extension will just be added to new instances
	 * of the decorated class. Use the static method {@link has_extension()}
	 * to check if a class (not an instance) has a specific extension.
	 * Caution: Don't use singleton(<class>)->hasExtension() as it will
	 * give you inconsistent results based on when the singleton was first
	 * accessed.
	 *
	 * @param string $extension Classname of an {@link Extension} subclass without parameters
	 * @return bool
	 */
	public function hasExtension($extension) {
		return isset($this->extension_instances[$extension]);
	}
	
	/**
	 * Get all extension instances for this specific object instance.
	 * See {@link get_extensions()} to get all applied extension classes
	 * for this class (not the instance).
	 * 
	 * @return array Map of {@link DataObjectDecorator} instances, keyed by classname.
	 */
	public function getExtensionInstances() {
		return $this->extension_instances;
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
	 * Clears the cache for the given cacheToFile call
	 */
	public function clearCache($method, $ID = false, $arguments = array()) {
		$cacheName = $this->class . '_' . $method;
		if(!is_array($arguments)) $arguments = array($arguments);
		if($ID) $cacheName .= '_' . $ID;
		if(count($arguments)) $cacheName .= '_' . implode('_', $arguments);

		$file = TEMP_FOLDER . '/' . $this->sanitiseCachename($cacheName);
		if(file_exists($file)) unlink($file);
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
	
	/**
	 * @deprecated 2.4 Use getExtensionInstance
	 */
	public function extInstance($extension) {
		user_error('Object::extInstance() is deprecated. Please use Object::getExtensionInstance() instead.', E_USER_NOTICE);
		return $this->getExtensionInstance($extension);
	}
	
}
