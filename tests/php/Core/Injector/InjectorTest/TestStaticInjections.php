<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class TestStaticInjections implements TestOnly
{
    public $backend;

    /** @config */
    private static $dependencies = [
        'backend' => '%$SilverStripe\\Core\\Tests\\Injector\\InjectorTest\\NewRequirementsBackend'
    ];
}
