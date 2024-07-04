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
     * @param string|object $class - string: The FQCN of the class, object: A class instance
     */
    public function create(string $class, array $params = []): object
    {
        if (!class_exists($class)) {
            throw new InjectorNotFoundException("Class {$class} does not exist");
        }

        // Ensure there are no string keys as they cannot be unpacked with the `...` operator
        $values = array_values($params);

        return new $class(...$values);
    }
}
