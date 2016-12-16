<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

/**
 * Make sure DI works with ViewableData's implementation of __isset
 */
class TestSetterInjections extends ViewableData implements TestOnly
{

    protected $backend;

    /**
 * @config
*/
    private static $dependencies = array(
        'backend' => '%$SilverStripe\\Core\\Tests\\Injector\\InjectorTest\\NewRequirementsBackend'
    );

    public function getBackend()
    {
        return $this->backend;
    }

    public function setBackend($backend)
    {
        $this->backend = $backend;
    }
}
