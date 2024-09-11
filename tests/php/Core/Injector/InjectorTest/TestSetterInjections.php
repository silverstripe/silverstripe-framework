<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

/**
 * Make sure DI works with ModelData's implementation of __isset
 */
class TestSetterInjections extends ModelData implements TestOnly
{

    protected $backend;

    /**
 * @config
*/
    private static $dependencies = [
        'backend' => '%$SilverStripe\\Core\\Tests\\Injector\\InjectorTest\\NewRequirementsBackend'
    ];

    public function getBackend()
    {
        return $this->backend;
    }

    public function setBackend($backend)
    {
        $this->backend = $backend;
    }
}
