<?php

namespace SilverStripe\Core\Injector;

use ReflectionClass;
use ReflectionException;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{

    public function create($class, array $params = array())
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new InjectorNotFoundException($e);
        }

        if (count($params)) {
            return $reflector->newInstanceArgs($params);
        }

        return $reflector->newInstance();
    }
}
