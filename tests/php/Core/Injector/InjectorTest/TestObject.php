<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

class TestObject implements TestOnly
{
    use Injectable;

    const TEST_BAR = 'bar';
    const TEST_BAZ = 'baz';

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

    public function foo(): string
    {
        return self::TEST_BAR;
    }
}
