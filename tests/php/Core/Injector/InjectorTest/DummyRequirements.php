<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class DummyRequirements implements TestOnly
{

    public $backend;

    public function __construct($backend)
    {
        $this->backend = $backend;
    }

    public function setBackend($backend)
    {
        $this->backend = $backend;
    }
}
