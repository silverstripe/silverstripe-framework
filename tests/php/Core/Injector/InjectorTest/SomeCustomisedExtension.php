<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

class SomeCustomisedExtension extends SomeExtension
{
    public function someMethod()
    {
        return 'bar';
    }
}
