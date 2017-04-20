<?php

namespace SilverStripe\Core\Injector;

use ReflectionClass;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{

    public function create($class, array $params = array())
    {
        $reflector = new ReflectionClass($class);

        if (count($params)) {
            return $reflector->newInstanceArgs($params);
        }

        return $reflector->newInstance();
    }
}
