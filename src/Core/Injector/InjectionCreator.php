<?php

namespace SilverStripe\Core\Injector;

use InvalidArgumentException;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{
    /**
     * Create a new instance of a class
     *
     * Passing an object for $class will result from using an anonymous class in unit testing, e.g.
     * Injector::inst()->load([SomeClass::class => ['class' => new class { ... }]]);
     *
     * @param string|object $class - string: The FQCN of the class, object: A class instance
     */
    public function create($class, array $params = [])
    {
        if (is_object($class ?? '')) {
            $class = get_class($class);
        }
        if (!is_string($class ?? '')) {
            throw new InvalidArgumentException('$class parameter must be a string or an object');
        }
        if (!class_exists($class)) {
            throw new InjectorNotFoundException("Class {$class} does not exist");
        }

        // Ensure there are no string keys as they cannot be unpacked with the `...` operator
        $values = array_values($params);

        return new $class(...$values);
    }
}
