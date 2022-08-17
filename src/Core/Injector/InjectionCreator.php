<?php

namespace SilverStripe\Core\Injector;

/**
 * A class for creating new objects by the injector.
 */
class InjectionCreator implements Factory
{
    public function create(string $class, array $params = []): emteknetnz\TypeTransitioner\DevBuildExtension|i18nProviderClass|i18nTestModule|SilverStripe\Core\Manifest\PrioritySorter
    {
        if (!class_exists($class ?? '')) {
            throw new InjectorNotFoundException("Class {$class} does not exist");
        }
        // Ensure there are no string keys as they cannot be unpacked with the `...` operator
        $values = array_values($params ?? []);
        return new $class(...$values);
    }
}
