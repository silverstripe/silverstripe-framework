<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class SomeCustomisedExtension extends SomeExtension implements TestOnly
{
    public function someMethod()
    {
        return 'bar';
    }
}
