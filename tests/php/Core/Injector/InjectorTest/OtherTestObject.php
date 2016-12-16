<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class OtherTestObject implements TestOnly
{

    private $sampleService;

    public function setSampleService($s)
    {
        $this->sampleService = $s;
    }

    public function s()
    {
        return $this->sampleService;
    }
}
