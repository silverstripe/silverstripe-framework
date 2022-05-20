<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\Injector\SilverStripeServiceConfigurationLocator;
use SilverStripe\Dev\TestOnly;

class InjectorTestConfigLocator extends SilverStripeServiceConfigurationLocator implements TestOnly
{

    protected function configFor($name)
    {

        switch ($name) {
            case TestObject::class:
                return $this->configs[$name] = [
                    'class' => ConstructableObject::class,
                    'constructor' => [
                        '%$' . OtherTestObject::class
                    ]
                ];

            case 'ConfigConstructor':
                return $this->configs[$name] = [
                    'class' => ConstructableObject::class,
                    'constructor' => ['value']
                ];
        }

        return parent::configFor($name);
    }
}
