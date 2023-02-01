<?php

namespace SilverStripe\Core;

use BadMethodCallException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Allows an object to declare a set of custom methods
 */
trait CustomMethods
{
    /**
     * Custom method sources
     *
     * @var array Array of class names (lowercase) to list of methods.
     * The list of methods will have lowercase keys. Each value in this array
     * can be a callable, array, or string callback
     */
    protected static $extra_methods = [];

    /**
     * Name of methods to invoke by defineMethods for this instance
     *
     * @var array
     */
    protected $extra_method_registers = [];

    /**
     * Non-custom public methods.
     *
     * @var array Array of class names (lowercase) to list of methods.
     * The list of methods will have lowercase keys and correct-case values.
     */
    protected static $built_in_methods = [];

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

                // Call on object
                try {
                    if ($obj instanceof Extension) {
                        $obj->setOwner($this);
                    }
                    return $obj->$method(...$arguments);
                } finally {
                    if ($obj instanceof Extension) {
                        $obj->clearOwner();
                    }
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
        return method_exists($this, $method ?? '') || $this->hasCustomMethod($method);
    }

    /**
     * Determines if a custom method with this name is defined.
     */
    protected function hasCustomMethod($method): bool
    {
        return $this->getExtraMethodConfig($method) !== null;
    }

    /**
     * Get meta-data details on a named method
     *
     * @param string $method
     * @return array List of custom method details, if defined for this method
     */
    protected function getExtraMethodConfig($method)
    {
        if (empty($method)) {
            return null;
        }
        // Lazy define methods
        $lowerClass = strtolower(static::class);
        if (!isset(self::$extra_methods[$lowerClass])) {
            $this->defineMethods();
        }

        return self::$extra_methods[$lowerClass][strtolower($method)] ?? null;
    }

    /**
     * Return the names of all the methods available on this object
     *
     * @param bool $custom include methods added dynamically at runtime
     * @return array Map of method names with lowercase keys
     */
    public function allMethodNames($custom = false)
    {
        $methods = static::findBuiltInMethods();

        // Query extra methods
        $lowerClass = strtolower(static::class);
        if ($custom && isset(self::$extra_methods[$lowerClass])) {
            $methods = array_merge(self::$extra_methods[$lowerClass], $methods);
        }

        return $methods;
    }

    /**
     * Get all public built in methods for this class
     *
     * @param string|object $class Class or instance to query methods from (defaults to static::class)
     * @return array Map of methods with lowercase key name
     */
    protected static function findBuiltInMethods($class = null)
    {
        $class = is_object($class) ? get_class($class) : ($class ?: static::class);
        $lowerClass = strtolower($class);
        if (isset(self::$built_in_methods[$lowerClass])) {
            return self::$built_in_methods[$lowerClass];
        }

        // Build new list
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        self::$built_in_methods[$lowerClass] = [];
        foreach ($methods as $method) {
            $name = $method->getName();
            self::$built_in_methods[$lowerClass][strtolower($name)] = $name;
        }
        return self::$built_in_methods[$lowerClass];
    }

    /**
     * Find all methods on the given object.
     *
     * @param object $object
     * @return array
     */
    protected function findMethodsFrom($object)
    {
        // Respect "allMethodNames"
        if (method_exists($object, 'allMethodNames')) {
            if ($object instanceof Extension) {
                try {
                    $object->setOwner($this);
                    $methods = $object->allMethodNames(true);
                } finally {
                    $object->clearOwner();
                }
            } else {
                $methods = $object->allMethodNames(true);
            }
            return $methods;
        }

        // Get methods
        return static::findBuiltInMethods($object);
    }

    /**
     * Add all the methods from an object property.
     *
     * @param string $property the property name
     * @param string|int $index an index to use if the property is an array
     * @throws InvalidArgumentException
     */
    protected function addMethodsFrom($property, $index = null)
    {
        $class = static::class;
        $object = ($index !== null) ? $this->{$property}[$index] : $this->$property;

        if (!$object) {
            throw new InvalidArgumentException(
                "Object->addMethodsFrom(): could not add methods from {$class}->{$property}[$index]"
            );
        }

        $methods = $this->findMethodsFrom($object);
        if (!$methods) {
            return;
        }
        $methodInfo = [
            'property' => $property,
            'index' => $index,
        ];

        $newMethods = array_fill_keys(array_keys($methods), $methodInfo);

        // Merge with extra_methods
        $lowerClass = strtolower($class);
        if (isset(self::$extra_methods[$lowerClass])) {
            self::$extra_methods[$lowerClass] = array_merge(self::$extra_methods[$lowerClass], $newMethods);
        } else {
            self::$extra_methods[$lowerClass] = $newMethods;
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

        $lowerClass = strtolower($class);
        if (!isset(self::$extra_methods[$lowerClass])) {
            return;
        }
        $methods = $this->findMethodsFrom($extension);

        // Unset by key
        self::$extra_methods[$lowerClass] = array_diff_key(self::$extra_methods[$lowerClass], $methods);

        // Clear empty list
        if (empty(self::$extra_methods[$lowerClass])) {
            unset(self::$extra_methods[$lowerClass]);
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
        self::$extra_methods[strtolower(static::class)][strtolower($method)] = [
            'wrap' => $wrap,
            'method' => $method
        ];
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
        self::$extra_methods[strtolower(static::class)][strtolower($method)] = [
            'callback' => $callback,
        ];
    }
}
