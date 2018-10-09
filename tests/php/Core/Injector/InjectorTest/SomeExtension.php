<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\ORM\DataExtension;

class SomeExtension extends DataExtension
{
    public function someMethod()
    {
        return 'foo';
    }
}
