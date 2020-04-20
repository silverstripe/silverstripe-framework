<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\Injector\Factory;

class EmptyFactory implements Factory
{
    public function create($service, array $params = [])
    {
        return null;
    }
}
