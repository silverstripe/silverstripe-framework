<?php

namespace SilverStripe\Core;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\ViewableData;

/**
 * Allows an object to have extensions applied to it.
 */
trait Extensible
{
    use CustomMethods {
        defineMethods as defineMethodsCustom;
    }

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
    private static $extensions = [];

    /**
     * Classes that cannot be extended
     *
     * @var array
     */
    private static $unextendable_classes = [
        ViewableData::class,
    ];

    /**
     * @var Extension[] all current extension instances, or null if not declared yet.
     */
    protected $extension_instances = null;

    /**
     * List of callbacks to call prior to extensions having extend called on them,
     * each grouped by methodName.
     *
     * Top level array is method names, each of which is an array of callbacks for that name.
     *
     * @var callable[][]
     */
    protected $beforeExtendCallbacks = [];

    /**
     * List of callbacks to call after extensions having extend called on them,
     * each grouped by methodName.
     *
     * Top level array is method names, each of which is an array of callbacks for that name.
     *
     * @var callable[][]
     */
    protected $afterExtendCallbacks = [];

    /**
     * Allows user code to hook into Object::extend prior to control
     * being delegated to extensions. Each callback will be reset
     * once called.
     *
     * @param string $method The name of the method to hook into
     * @param callable $callback The callback to execute
     */
    protected function beforeExtending($method, $callback)
    {
        if (empty($this->beforeExtendCallbacks[$method])) {
            $this->beforeExtendCallbacks[$method] = [];
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
    protected function afterExtending($method, $callback)
    {
        if (empty($this->afterExtendCallbacks[$method])) {
            $this->afterExtendCallbacks[$method] = [];
        }
        $this->afterExtendCallbacks[$method][] = $callback;
    }

    protected function defineMethods()
    {
        $this->defineMethodsCustom();

        // Define extension methods
        $this->defineExtensionMethods();
    }

    /**
     * Adds any methods from {@link Extension} instances attached to this object.
     * All these methods can then be called directly on the instance (transparently
     * mapped through {@link __call()}), or called explicitly through {@link extend()}.
     *
     * @uses addCallbackMethod()
     */
    protected function defineExtensionMethods()
    {
        $extensions = $this->getExtensionInstances();
        foreach ($extensions as $extensionClass => $extensionInstance) {
            foreach ($this->findMethodsFrom($extensionInstance) as $method) {
                $this->addCallbackMethod($method, function ($inst, $args) use ($method, $extensionClass) {
                    /** @var Extensible $inst */
                    $extension = $inst->getExtensionInstance($extensionClass);
                    if (!$extension) {
                        return null;
                    }

                    try {
                        $extension->setOwner($inst);
                        return call_user_func_array([$extension, $method], $args ?? []);
                    } finally {
                        $extension->clearOwner();
                    }
                });
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
     *  as a string, e.g. "Versioned"
     * @return bool Flag if the extension was added
     */
    public static function add_extension($classOrExtension, $extension = null)
    {
        if ($extension) {
            $class = $classOrExtension;
        } else {
            $class = get_called_class();
            $extension = $classOrExtension;
        }

        if (!preg_match('/^([^(]*)/', $extension ?? '', $matches)) {
            return false;
        }
        $extensionClass = $matches[1];
        if (!class_exists($extensionClass ?? '')) {
            throw new InvalidArgumentException(sprintf(
                'Object::add_extension() - Can\'t find extension class for "%s"',
                $extensionClass
            ));
        }

        if (!is_subclass_of($extensionClass, Extension::class)) {
            throw new InvalidArgumentException(sprintf(
                'Object::add_extension() - Extension "%s" is not a subclass of Extension',
                $extensionClass
            ));
        }

        // unset some caches
        $subclasses = ClassInfo::subclassesFor($class);
        $subclasses[] = $class;
        foreach ($subclasses as $subclass) {
            unset(self::$extra_methods[strtolower($subclass)]);
        }

        Config::modify()
            ->merge($class, 'extensions', [
                $extension
            ]);

        Injector::inst()->unregisterNamedObject($class);
        return true;
    }

    /**
     * Remove an extension from a class.
     * Note: This will not remove extensions from parent classes, and must be called
     * directly on the class assigned the extension.
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
     * @param string $extension class name of an {@link Extension} subclass, without parameters
     */
    public static function remove_extension($extension)
    {
        $class = get_called_class();

        // Build filtered extension list
        $found = false;
        $config = Config::inst()->get($class, 'extensions', Config::EXCLUDE_EXTRA_SOURCES | Config::UNINHERITED) ?: [];
        foreach ($config as $key => $candidate) {
            // extensions with parameters will be stored in config as ExtensionName("Param").
            if (strcasecmp($candidate ?? '', $extension ?? '') === 0 ||
                stripos($candidate ?? '', $extension . '(') === 0
            ) {
                $found = true;
                unset($config[$key]);
            }
        }
        // Don't dirty cache if no changes
        if (!$found) {
            return;
        }
        Config::modify()->set($class, 'extensions', $config);

        // Unset singletons
        Injector::inst()->unregisterObjects($class);

        // unset some caches
        $subclasses = ClassInfo::subclassesFor($class);
        $subclasses[] = $class;
        foreach ($subclasses as $subclass) {
            unset(self::$extra_methods[strtolower($subclass)]);
        }
    }

    /**
     * @param string $class If omitted, will get extensions for the current class
     * @param bool $includeArgumentString Include the argument string in the return array,
     *  FALSE would return array("Versioned"), TRUE returns array("Versioned('Stage','Live')").
     * @return array Numeric array of either {@link DataExtension} class names,
     *  or eval'ed class name strings with constructor arguments.
     */
    public static function get_extensions($class = null, $includeArgumentString = false)
    {
        if (!$class) {
            $class = get_called_class();
        }

        $extensions = Config::forClass($class)->get('extensions', Config::EXCLUDE_EXTRA_SOURCES);
        if (empty($extensions)) {
            return [];
        }

        // Clean nullified named extensions
        $extensions = array_filter(array_values($extensions ?? []));

        if ($includeArgumentString) {
            return $extensions;
        } else {
            $extensionClassnames = [];
            if ($extensions) {
                foreach ($extensions as $extension) {
                    $extensionClassnames[] = Extension::get_classname_without_arguments($extension);
                }
            }
            return $extensionClassnames;
        }
    }


    /**
     * Get extra config sources for this class
     *
     * @param string $class Name of class. If left null will return for the current class
     * @return array|null
     */
    public static function get_extra_config_sources($class = null)
    {
        if (!$class) {
            $class = get_called_class();
        }

        // If this class is unextendable, NOP
        if (in_array($class, self::$unextendable_classes)) {
            return null;
        }

        // Variable to hold sources in
        $sources = null;

        // Get a list of extensions
        $extensions = Config::inst()->get($class, 'extensions', Config::EXCLUDE_EXTRA_SOURCES | Config::UNINHERITED);

        if (!$extensions) {
            return null;
        }

        // Build a list of all sources;
        $sources = [];

        foreach ($extensions as $extension) {
            [$extensionClass, $extensionArgs] = ClassInfo::parse_class_spec($extension);
            // Strip service name specifier
            $extensionClass = strtok($extensionClass ?? '', '.');
            $sources[] = $extensionClass;

            if (!class_exists($extensionClass ?? '')) {
                throw new InvalidArgumentException("$class references nonexistent $extensionClass in \$extensions");
            }

            call_user_func([$extensionClass, 'add_to_class'], $class, $extensionClass, $extensionArgs);

            foreach (array_reverse(ClassInfo::ancestry($extensionClass) ?? []) as $extensionClassParent) {
                if (ClassInfo::has_method_from($extensionClassParent, 'get_extra_config', $extensionClassParent)) {
                    $extras = $extensionClassParent::get_extra_config($class, $extensionClass, $extensionArgs);
                    if ($extras) {
                        $sources[] = $extras;
                    }
                }
            }
        }

        return $sources;
    }


    /**
     * Return TRUE if a class has a specified extension.
     * This supports backwards-compatible format (static Object::has_extension($requiredExtension))
     * and new format ($object->has_extension($class, $requiredExtension))
     * @param string $classOrExtension Class to check extension for, or the extension name to check
     * if the second argument is null.
     * @param string $requiredExtension If the first argument is the parent class, this is the extension to check.
     * If left null, the first parameter will be treated as the extension.
     * @param boolean $strict if the extension has to match the required extension and not be a subclass
     * @return bool Flag if the extension exists
     */
    public static function has_extension($classOrExtension, $requiredExtension = null, $strict = false)
    {
        if ($requiredExtension) {
            $class = $classOrExtension;
        } else {
            $class = get_called_class();
            $requiredExtension = $classOrExtension;
        }

        $requiredExtension = Extension::get_classname_without_arguments($requiredExtension);
        $extensions = self::get_extensions($class);
        foreach ($extensions as $extension) {
            if (strcasecmp($extension ?? '', $requiredExtension ?? '') === 0) {
                return true;
            }
            if (!$strict && is_subclass_of($extension, $requiredExtension ?? '')) {
                return true;
            }
            $inst = Injector::inst()->get($extension);
            if ($inst instanceof $requiredExtension) {
                return $strict ? strcasecmp(get_class($inst), $requiredExtension) === 0 : true;
            }
        }

        return false;
    }


    /**
     * Calls a method if available on both this object and all applied {@link Extensions}, and then attempts to merge
     * all results into an array
     *
     * @param string $method the method name to call
     * @param mixed ...$arguments List of arguments
     * @return array List of results with nulls filtered out
     */
    public function invokeWithExtensions($method, &...$arguments)
    {
        $result = [];
        if (method_exists($this, $method ?? '')) {
            $thisResult = $this->$method(...$arguments);
            if ($thisResult !== null) {
                $result[] = $thisResult;
            }
        }
        $extras = $this->extend($method, ...$arguments);

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
     * @param mixed &...$arguments
     * @return array
     */
    public function extend($method, &...$arguments)
    {
        $values = [];

        if (!empty($this->beforeExtendCallbacks[$method])) {
            foreach (array_reverse($this->beforeExtendCallbacks[$method ?? '']) as $callback) {
                $value = call_user_func_array($callback, $arguments);
                if ($value !== null) {
                    $values[] = $value;
                }
            }
            $this->beforeExtendCallbacks[$method] = [];
        }

        foreach ($this->getExtensionInstances() as $instance) {
            // Prefer `extend` prefixed methods
            $value = $instance->invokeExtension($this, $method, ...$arguments);
            if ($value !== null) {
                $values[] = $value;
            }
        }

        if (!empty($this->afterExtendCallbacks[$method])) {
            foreach (array_reverse($this->afterExtendCallbacks[$method ?? '']) as $callback) {
                $value = call_user_func_array($callback, $arguments);
                if ($value !== null) {
                    $values[] = $value;
                }
            }
            $this->afterExtendCallbacks[$method] = [];
        }

        return $values;
    }

    /**
     * Get an extension instance attached to this object by name.
     *
     * @param string $extension
     * @return Extension|null
     */
    public function getExtensionInstance($extension)
    {
        $instances = $this->getExtensionInstances();
        if (array_key_exists($extension, $instances ?? [])) {
            return $instances[$extension];
        }
        // in case Injector has been used to replace an extension
        foreach ($instances as $instance) {
            if (is_a($instance, $extension ?? '')) {
                return $instance;
            }
        }
        return null;
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
    public function hasExtension($extension)
    {
        return (bool) $this->getExtensionInstance($extension);
    }

    /**
     * Get all extension instances for this specific object instance.
     * See {@link get_extensions()} to get all applied extension classes
     * for this class (not the instance).
     *
     * This method also provides lazy-population of the extension_instances property.
     *
     * @return Extension[] Map of {@link DataExtension} instances, keyed by classname.
     */
    public function getExtensionInstances()
    {
        if (isset($this->extension_instances)) {
            return $this->extension_instances;
        }

        // Setup all extension instances for this instance
        $this->extension_instances = [];
        foreach (ClassInfo::ancestry(static::class) as $class) {
            if (in_array($class, self::$unextendable_classes)) {
                continue;
            }
            $extensions = Config::inst()->get($class, 'extensions', Config::UNINHERITED | Config::EXCLUDE_EXTRA_SOURCES);

            if ($extensions) {
                foreach ($extensions as $extension) {
                    // Allow removing extensions via yaml config by setting named extension config to null
                    if ($extension === null) {
                        continue;
                    }

                    $name = $extension;
                    // Allow service names of the form "%$ServiceName"
                    if (substr($name ?? '', 0, 2) == '%$') {
                        $name = substr($name ?? '', 2);
                    }
                    $name = trim(strtok($name ?? '', '(') ?? '');
                    if (class_exists($name ?? '')) {
                        $name = ClassInfo::class_name($name);
                    }
                    $this->extension_instances[$name] = Injector::inst()->get($extension);
                }
            }
        }

        return $this->extension_instances;
    }
}
