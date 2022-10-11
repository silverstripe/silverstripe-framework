<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

class TestObjectTwo implements TestOnly
{
    public function fooViaTestObject(): string
    {
        $obj1 = TestObject::create();

        return $obj1->foo();
    }
}
