<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\Injector\Factory;

class EmptyFactory implements Factory
{
    public function create($service, array $params = array())
    {
        return null;
    }
}
