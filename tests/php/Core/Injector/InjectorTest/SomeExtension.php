<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class SomeExtension extends DataExtension implements TestOnly
{
    public function someMethod()
    {
        return 'foo';
    }
}
