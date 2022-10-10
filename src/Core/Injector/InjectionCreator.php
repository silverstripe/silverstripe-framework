<?php

namespace SilverStripe\Core\Injector;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{
    public function create($class, array $params = [])
    {
        // Allow anonymous classes or other classes to pass without ReflectionClass
        if (is_object($class)) {
            $values = $this->removeStringKeys($params);

            return new $class(...$values);
        }
        elseif(!class_exists($class ?? '')) {
            throw new InjectorNotFoundException("Class {$class} does not exist");
        }

        $values = $this->removeStringKeys($params);

        return new $class(...$values);
    }

    /**
     * @param $params
     *
     * Ensure there are no string keys as they cannot be unpacked with the `...` operator
     *
     * @return array
     */
    private function removeStringKeys($params): array
    {
        return array_values($params ?? []);
    }
}
