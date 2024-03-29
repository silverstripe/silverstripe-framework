<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Dev\TestOnly;

class TestObject implements TestOnly
{
    public $auto;

    public $sampleService;

    public $methodCalls = [];

    public function setSomething($v)
    {
        $this->sampleService = $v;
    }

    public function myMethod($arg)
    {
        $this->methodCalls[] = $arg;
    }

    public function noArgMethod()
    {
        $this->methodCalls[] = 'noArgMethod called';
    }

    protected function protectedMethod()
    {
        $this->methodCalls[] = 'protectedMethod called';
    }
}
