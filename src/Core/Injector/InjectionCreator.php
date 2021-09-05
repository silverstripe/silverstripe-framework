<?php

namespace SilverStripe\Core\Injector;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{
    public function create($class, array $params = [])
    {
        if (!class_exists($class)) {
            throw new InjectorNotFoundException(sprintf('Class "%s" does not exist', $class));
        }
        if (empty($params)) {
            return new $class();
        }
        // Remove named keys to ensure that PHP7 and PHP8 interpret these the same way
        $args = array_values($params);
        return new $class(...$args);
    }
}
