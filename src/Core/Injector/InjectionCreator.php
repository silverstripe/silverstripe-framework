<?php

namespace SilverStripe\Core\Injector;

use ReflectionClass;
use ReflectionException;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{

    public function create($class, array $params = [])
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new InjectorNotFoundException($e);
        }

        if (count($params)) {
            // Remove named keys to ensure that PHP7 and PHP8 interpret these the same way
            $params = array_values($params);
            return $reflector->newInstanceArgs($params);
        }

        return $reflector->newInstance();
    }
}
