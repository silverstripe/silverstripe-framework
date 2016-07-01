<?php

namespace SilverStripe\Framework\Core;

use ClassInfo;
use Config;
use Extension;
use Injector;
use InvalidArgumentException;

/**
 * Allows an object to have extensions applied to it.
 *
 * Bootstrap by calling $this->constructExtensions() in your class constructor.
 *
 * Requires CustomMethods trait
 */
trait Extensible {
	use CustomMethods;

	/**
	 * An array of extension names and parameters to be applied to this object upon construction.
	 *
	 * Example:
	 * <code>
	 * private static $extensions = array (
	 *   'Hierarchy',
	 *   "Version('Stage', 'Live')"
	 * );
	 * </code>
	 *
	 * Use {@link Object::add_extension()} to add extensions without access to the class code,
	 * e.g. to extend core classes.
	 *
	 * Extensions are instantiated together with the object and stored in {@link $extension_instances}.
	 *
	 * @var array $extensions
	 * @config
	 */
	private static $extensions = null;

	private static $classes_constructed = array();

	/**
	 * Classes that cannot be extended
	 *
	 * @var array
	 */
	private static $unextendable_classes = array('Object', 'ViewableData', 'RequestHandler');

	/**
	 * @var Extension[] all current extension instances.
	 */
	protected $extension_instances = array();

	/**
	 * List of callbacks to call prior to extensions having extend called on them,
	 * each grouped by methodName.
	 *
	 * Top level array is method names, each of which is an array of callbacks for that name.
	 *
	 * @var callable[][]
	 */
	protected $beforeExtendCallbacks = array();

	/**
	 * List of callbacks to call after extensions having extend called on them,
	 * each grouped by methodName.
	 *
	 * Top level array is method names, each of which is an array of callbacks for that name.
	 *
	 * @var callable[][]
	 */
	protected $afterExtendCallbacks = array();

	/**
	 * Allows user code to hook into Object::extend prior to control
	 * being delegated to extensions. Each callback will be reset
	 * once called.
	 *
	 * @param string $method The name of the method to hook into
	 * @param callable $callback The callback to execute
	 */
	protected function beforeExtending($method, $callback) {
		if(empty($this->beforeExtendCallbacks[$method])) {
			$this->beforeExtendCallbacks[$method] = array();
		}
		$this->beforeExtendCallbacks[$method][] = $callback;
	}

	/**
	 * Allows user code to hook into Object::extend after control
	 * being delegated to extensions. Each callback will be reset
	 * once called.
	 *
	 * @param string $method The name of the method to hook into
	 * @param callable $callback The callback to execute
	 */
	protected function afterExtending($method, $callback) {
		if(empty($this->afterExtendCallbacks[$method])) {
			$this->afterExtendCallbacks[$method] = array();
		}
		$this->afterExtendCallbacks[$method][] = $callback;
	}

	protected function constructExtensions() {
		$class = get_class($this);

		// Register this trait as a method source
		$this->registerExtraMethodCallback('defineExtensionMethods', function() {
			$this->defineExtensionMethods();
		});

		// Setup all extension instances for this instance
		foreach(ClassInfo::ancestry($class) as $class) {
			if(in_array($class, self::$unextendable_classes)) continue;
			$extensions = Config::inst()->get($class, 'extensions',
				Config::UNINHERITED | Config::EXCLUDE_EXTRA_SOURCES);

			if($extensions) foreach($extensions as $extension) {
				$instance = \Object::create_from_string($extension);
				$instance->setOwner(null, $class);
				$this->extension_instances[$instance->class] = $instance;
			}
		}

		if(!isset(self::$classes_constructed[$class])) {
			$this->defineMethods();
			self::$classes_constructed[$class] = true;
		}
	}

	/**
	 * Adds any methods from {@link Extension} instances attached to this object.
	 * All these methods can then be called directly on the instance (transparently
	 * mapped through {@link __call()}), or called explicitly through {@link extend()}.
	 *
	 * @uses addMethodsFrom()
	 */
	protected function defineExtensionMethods() {
		if(!empty($this->extension_instances)) {
			foreach (array_keys($this->extension_instances) as $key) {
				$this->addMethodsFrom('extension_instances', $key);
			}
		}
	}


	/**
	 * Add an extension to a specific class.
	 *
	 * The preferred method for adding extensions is through YAML config,
	 * since it avoids autoloading the class, and is easier to override in
	 * more specific configurations.
	 *
	 * As an alternative, extensions can be added to a specific class
	 * directly in the {@link Object::$extensions} array.
	 * See {@link SiteTree::$extensions} for examples.
	 * Keep in mind that the extension will only be applied to new
	 * instances, not existing ones (including all instances created through {@link singleton()}).
	 *
	 * @see http://doc.silverstripe.org/framework/en/trunk/reference/dataextension
	 * @param string $classOrExtension Class that should be extended - has to be a subclass of {@link Object}
	 * @param string $extension Subclass of {@link Extension} with optional parameters
	 *  as a string, e.g. "Versioned" or "Translatable('Param')"
	 * @return bool Flag if the extension was added
	 */
	public static function add_extension($classOrExtension, $extension = null) {
		if(func_num_args() > 1) {
			$class = $classOrExtension;
		} else {
			$class = get_called_class();
			$extension = $classOrExtension;
		}

		if(!preg_match('/^([^(]*)/', $extension, $matches)) {
			return false;
		}
		$extensionClass = $matches[1];
		if(!class_exists($extensionClass)) {
			user_error(
				sprintf('Object::add_extension() - Can\'t find extension class for "%s"', $extensionClass),
				E_USER_ERROR
			);
		}

		if(!is_subclass_of($extensionClass, 'Extension')) {
			user_error(
				sprintf('Object::add_extension() - Extension "%s" is not a subclass of Extension', $extensionClass),
				E_USER_ERROR
			);
		}

		// unset some caches
		$subclasses = ClassInfo::subclassesFor($class);
		$subclasses[] = $class;

		if($subclasses) foreach($subclasses as $subclass) {
			unset(self::$classes_constructed[$subclass]);
			unset(self::$extra_methods[$subclass]);
		}

		Config::inst()->update($class, 'extensions', array($extension));
		Config::inst()->extraConfigSourcesChanged($class);

		Injector::inst()->unregisterNamedObject($class);

		// load statics now for DataObject classes
		if(is_subclass_of($class, 'SilverStripe\\ORM\\DataObject')) {
			if(!is_subclass_of($extensionClass, 'SilverStripe\\ORM\\DataExtension')) {
				user_error("$extensionClass cannot be applied to $class without being a DataExtension", E_USER_ERROR);
			}
		}
		return true;
	}


	/**
	 * Remove an extension from a class.
	 *
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
	 * @param string $extension class name of an {@link Extension} subclass, without parameters
	 */
	public static function remove_extension($extension) {
		$class = get_called_class();

		Config::inst()->remove($class, 'extensions', Config::anything(), $extension);

		// remove any instances of the extension with parameters
		$config = Config::inst()->get($class, 'extensions');

		if($config) {
			foreach($config as $k => $v) {
				// extensions with parameters will be stored in config as
				// ExtensionName("Param").
				if(preg_match(sprintf("/^(%s)\(/", preg_quote($extension, '/')), $v)) {
					Config::inst()->remove($class, 'extensions', Config::anything(), $v);
				}
			}
		}

		Config::inst()->extraConfigSourcesChanged($class);

		// unset singletons to avoid side-effects
		Injector::inst()->unregisterAllObjects();

		// unset some caches
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
	 * @return array Numeric array of either {@link DataExtension} class names,
	 *  or eval'ed class name strings with constructor arguments.
	 */
	public static function get_extensions($class, $includeArgumentString = false) {
		$extensions = Config::inst()->get($class, 'extensions');
		if(empty($extensions)) {
			return array();
		}

		// Clean nullified named extensions
		$extensions = array_filter(array_values($extensions));

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


	public static function get_extra_config_sources($class = null) {
		if($class === null) $class = get_called_class();

		// If this class is unextendable, NOP
		if(in_array($class, self::$unextendable_classes)) {
			return null;
		}

		// Variable to hold sources in
		$sources = null;

		// Get a list of extensions
		$extensions = Config::inst()->get($class, 'extensions', Config::UNINHERITED | Config::EXCLUDE_EXTRA_SOURCES);

		if(!$extensions) {
			return null;
		}

		// Build a list of all sources;
		$sources = array();

		foreach($extensions as $extension) {
			list($extensionClass, $extensionArgs) = \Object::parse_class_spec($extension);
			$sources[] = $extensionClass;

			if (!class_exists($extensionClass)) {
				throw new InvalidArgumentException("$class references nonexistent $extensionClass in \$extensions");
			}

			call_user_func(array($extensionClass, 'add_to_class'), $class, $extensionClass, $extensionArgs);

			foreach(array_reverse(ClassInfo::ancestry($extensionClass)) as $extensionClassParent) {
				if (ClassInfo::has_method_from($extensionClassParent, 'get_extra_config', $extensionClassParent)) {
					$extras = $extensionClassParent::get_extra_config($class, $extensionClass, $extensionArgs);
					if ($extras) $sources[] = $extras;
				}
			}
		}

		return $sources;
	}


	/**
	 * Return TRUE if a class has a specified extension.
	 * This supports backwards-compatible format (static Object::has_extension($requiredExtension))
	 * and new format ($object->has_extension($class, $requiredExtension))
	 * @param string $classOrExtension if 1 argument supplied, the class name of the extension to
	 *								 check for; if 2 supplied, the class name to test
	 * @param string $requiredExtension used only if 2 arguments supplied
	 * @param boolean $strict if the extension has to match the required extension and not be a subclass
	 * @return bool Flag if the extension exists
	 */
	public static function has_extension($classOrExtension, $requiredExtension = null, $strict = false) {
		//BC support
		if(func_num_args() > 1){
			$class = $classOrExtension;
		} else {
			$class = get_called_class();
			$requiredExtension = $classOrExtension;
		}

		$requiredExtension = Extension::get_classname_without_arguments($requiredExtension);
		$extensions = self::get_extensions($class);
		foreach($extensions as $extension) {
			if(strcasecmp($extension, $requiredExtension) === 0) {
				return true;
			}
			if (!$strict && is_subclass_of($extension, $requiredExtension)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Calls a method if available on both this object and all applied {@link Extensions}, and then attempts to merge
	 * all results into an array
	 *
	 * @param string $method the method name to call
	 * @param mixed $a1
	 * @param mixed $a2
	 * @param mixed $a3
	 * @param mixed $a4
	 * @param mixed $a5
	 * @param mixed $a6
	 * @param mixed $a7
	 * @return array List of results with nulls filtered out
	 */
	public function invokeWithExtensions($method, &$a1=null, &$a2=null, &$a3=null, &$a4=null, &$a5=null, &$a6=null, &$a7=null) {
		$result = array();
		if(method_exists($this, $method)) {
			$thisResult = $this->$method($a1, $a2, $a3, $a4, $a5, $a6, $a7);
			if($thisResult !== null) {
				$result[] = $thisResult;
			}
		}
		$extras = $this->extend($method, $a1, $a2, $a3, $a4, $a5, $a6, $a7);

		return $extras ? array_merge($result, $extras) : $result;
	}

	/**
	 * Run the given function on all of this object's extensions. Note that this method originally returned void, so if
	 * you wanted to return results, you're hosed
	 *
	 * Currently returns an array, with an index resulting every time the function is called. Only adds returns if
	 * they're not NULL, to avoid bogus results from methods just defined on the parent extension. This is important for
	 * permission-checks through extend, as they use min() to determine if any of the returns is FALSE. As min() doesn't
	 * do type checking, an included NULL return would fail the permission checks.
	 *
	 * The extension methods are defined during {@link __construct()} in {@link defineMethods()}.
	 *
	 * @param string $method the name of the method to call on each extension
	 * @param mixed $a1
	 * @param mixed $a2
	 * @param mixed $a3
	 * @param mixed $a4
	 * @param mixed $a5
	 * @param mixed $a6
	 * @param mixed $a7
	 * @return array
	 */
	public function extend($method, &$a1=null, &$a2=null, &$a3=null, &$a4=null, &$a5=null, &$a6=null, &$a7=null) {
		$values = array();

		if(!empty($this->beforeExtendCallbacks[$method])) {
			foreach(array_reverse($this->beforeExtendCallbacks[$method]) as $callback) {
				$value = call_user_func_array($callback, array(&$a1, &$a2, &$a3, &$a4, &$a5, &$a6, &$a7));
				if($value !== null) $values[] = $value;
			}
			$this->beforeExtendCallbacks[$method] = array();
		}

		if($this->extension_instances) foreach($this->extension_instances as $instance) {
			if(method_exists($instance, $method)) {
				$instance->setOwner($this);
				$value = $instance->$method($a1, $a2, $a3, $a4, $a5, $a6, $a7);
				if($value !== null) $values[] = $value;
				$instance->clearOwner();
			}
		}

		if(!empty($this->afterExtendCallbacks[$method])) {
			foreach(array_reverse($this->afterExtendCallbacks[$method]) as $callback) {
				$value = call_user_func_array($callback, array(&$a1, &$a2, &$a3, &$a4, &$a5, &$a6, &$a7));
				if($value !== null) $values[] = $value;
			}
			$this->afterExtendCallbacks[$method] = array();
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
	 * of the extended class. Use the static method {@link has_extension()}
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
	 * @return array Map of {@link DataExtension} instances, keyed by classname.
	 */
	public function getExtensionInstances() {
		return $this->extension_instances;
	}

}
