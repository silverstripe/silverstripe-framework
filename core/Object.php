<?php

use SilverStripe\Framework\Core\Configurable;
use SilverStripe\Framework\Core\Extensible;
use SilverStripe\Framework\Core\Injectable;

/**
 * A base class for all SilverStripe objects to inherit from.
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
 * @package framework
 * @subpackage core
 */
abstract class Object {
	use Extensible;
	use Injectable;
	use Configurable;

	/**
	 * @var string the class name
	 */
	public $class;

	private static $_cache_inst_args = array();

	/**
	 * Create an object from a string representation.  It treats it as a PHP constructor without the
	 * 'new' keyword.  It also manages to construct the object without the use of eval().
	 *
	 * Construction itself is done with Object::create(), so that Object::useCustomClass() calls
	 * are respected.
	 *
	 * `Object::create_from_string("Versioned('Stage','Live')")` will return the result of
	 * `Versioned::create('Stage', 'Live);`
	 *
	 * It is designed for simple, cloneable objects.  The first time this method is called for a given
	 * string it is cached, and clones of that object are returned.
	 *
	 * If you pass the $firstArg argument, this will be prepended to the constructor arguments. It's
	 * impossible to pass null as the firstArg argument.
	 *
	 * `Object::create_from_string("Varchar(50)", "MyField")` will return the result of
	 * `Varchar::create('MyField', '50');`
	 *
	 * Arguments are always strings, although this is a quirk of the current implementation rather
	 * than something that can be relied upon.
	 *
	 * @param string $classSpec
	 * @param mixed $firstArg
	 * @return object
	 */
	public static function create_from_string($classSpec, $firstArg = null) {
		if(!isset(self::$_cache_inst_args[$classSpec.$firstArg])) {
			// an $extension value can contain parameters as a string,
			// e.g. "Versioned('Stage','Live')"
			if(strpos($classSpec,'(') === false) {
				if($firstArg === null) {
					self::$_cache_inst_args[$classSpec.$firstArg] = Object::create($classSpec);
				} else {
					self::$_cache_inst_args[$classSpec.$firstArg] = Object::create($classSpec, $firstArg);
				}

			} else {
				list($class, $args) = self::parse_class_spec($classSpec);

				if($firstArg !== null) {
					array_unshift($args, $firstArg);
				}
				array_unshift($args, $class);

				self::$_cache_inst_args[$classSpec.$firstArg] = call_user_func_array(array('Object','create'), $args);
			}
		}

		return clone self::$_cache_inst_args[$classSpec.$firstArg];
	}

	/**
	 * Parses a class-spec, such as "Versioned('Stage','Live')", as passed to create_from_string().
	 * Returns a 2-element array, with classname and arguments
	 *
	 * @param string $classSpec
	 * @return array
	 * @throws Exception
	 */
	public static function parse_class_spec($classSpec) {
		$tokens = token_get_all("<?php $classSpec");
		$class = null;
		$args = array();

		// Keep track of the current bucket that we're putting data into
		$bucket = &$args;
		$bucketStack = array();
		$hadNamespace = false;
		$currentKey = null;

		foreach($tokens as $token) {
			// $forceResult used to allow null result to be detected
			$result = $forceResult = null;
			$tokenName = is_array($token) ? $token[0] : $token;

			// Get the class name
			if($class === null && is_array($token) && $token[0] === T_STRING) {
				$class = $token[1];
			} elseif(is_array($token) && $token[0] === T_NS_SEPARATOR) {
				$class .= $token[1];
				$hadNamespace = true;
			} elseif($hadNamespace && is_array($token) && $token[0] === T_STRING) {
				$class .= $token[1];
				$hadNamespace = false;
			// Get arguments
			} else if(is_array($token)) {
				switch($token[0]) {
				case T_CONSTANT_ENCAPSED_STRING:
					$argString = $token[1];
					switch($argString[0]) {
					case '"':
								$result = stripcslashes(substr($argString,1,-1));
						break;
					case "'":
								$result = str_replace(array("\\\\", "\\'"),array("\\", "'"), substr($argString,1,-1));
						break;
					default:
						throw new Exception("Bad T_CONSTANT_ENCAPSED_STRING arg $argString");
					}

					break;

				case T_DNUMBER:
						$result = (double)$token[1];
					break;

				case T_LNUMBER:
						$result = (int)$token[1];
						break;

					case T_DOUBLE_ARROW:
						// We've encountered an associative array (the array itself has already been
						// added to the bucket), so the previous item added to the bucket is the key
						end($bucket);
						$currentKey = current($bucket);
						array_pop($bucket);
					break;

				case T_STRING:
					switch($token[1]) {
							case 'true': $result = true; break;
							case 'false': $result = false; break;
							case 'null': $result = null; $forceResult = true; break;
						default: throw new Exception("Bad T_STRING arg '{$token[1]}'");
					}
						
					break;

				case T_ARRAY:
						$result = array();
						break;
				}
			} else {
				if($tokenName === '[') {
					$result = array();
				} elseif(($tokenName === ')' || $tokenName === ']') && ! empty($bucketStack)) {
					// Store the bucket we're currently working on
					$oldBucket = $bucket;
					// Fetch the key for the bucket at the top of the stack
					end($bucketStack);
					$key = key($bucketStack);
					reset($bucketStack);
					// Re-instate the bucket from the top of the stack
					$bucket = &$bucketStack[$key];
					// Add our saved, "nested" bucket to the bucket we just popped off the stack
					$bucket[$key] = $oldBucket;
					// Remove the bucket we just popped off the stack
					array_pop($bucketStack);
				}
			}

			// If we've got something to add to the bucket, add it
			if($result !== null || $forceResult) {
				if($currentKey) {
					$bucket[$currentKey] = $result;
					$currentKey = null;
				} else {
					$bucket[] = $result;
				}

				// If we've just pushed an array, that becomes our new bucket
				if($result === array()) {
					// Fetch the key that the array was pushed to
					end($bucket);
					$key = key($bucket);
					reset($bucket);
					// Store reference to "old" bucket in the stack
					$bucketStack[$key] = &$bucket;
					// Set the active bucket to be our newly-pushed, empty array
					$bucket = &$bucket[$key];
				}
			}
		}

		return array($class, $args);
	}

	/**
	 * Get the value of a static property of a class, even in that property is declared protected (but not private),
	 * without any inheritance, merging or parent lookup if it doesn't exist on the given class.
	 *
	 * @static
	 * @param $class - The class to get the static from
	 * @param $name - The property to get from the class
	 * @param null $default - The value to return if property doesn't exist on class
	 * @return any - The value of the static property $name on class $class, or $default if that property is not
	 *               defined
	 */
	public static function static_lookup($class, $name, $default = null) {
		if (is_subclass_of($class, 'Object')) {
			if (isset($class::$$name)) {
				$parent = get_parent_class($class);
				if (!$parent || !isset($parent::$$name) || $parent::$$name !== $class::$$name) return $class::$$name;
			}
			return $default;
		} else {
			// TODO: This gets set once, then not updated, so any changes to statics after this is called the first
			// time for any class won't be exposed
			static $static_properties = array();

			if (!isset($static_properties[$class])) {
				$reflection = new ReflectionClass($class);
				$static_properties[$class] = $reflection->getStaticProperties();
			}

			if (isset($static_properties[$class][$name])) {
				$value = $static_properties[$class][$name];

				$parent = get_parent_class($class);
				if (!$parent) return $value;

				if (!isset($static_properties[$parent])) {
					$reflection = new ReflectionClass($parent);
					$static_properties[$parent] = $reflection->getStaticProperties();
				}

				if (!isset($static_properties[$parent][$name]) || $static_properties[$parent][$name] !== $value) {
					return $value;
				}
			}
		}

		return $default;
	}

	public function __construct() {
		$this->class = get_class($this);
		$this->constructExtensions();
	}

	// --------------------------------------------------------------------------------------------------------------

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

}
