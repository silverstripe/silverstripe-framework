<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class ConstructableObject implements TestOnly
{
    public $property;

    public function __construct($prop)
    {
        $this->property = $prop;
    }
}
