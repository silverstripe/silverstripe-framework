<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Control\Tests\FlushMiddlewareTest\TestFlushable;
use SilverStripe\Dev\FunctionalTest;

class FlushMiddlewareTest extends FunctionalTest
{
    /**
     * Assert that classes that implement flushable are called
     */
    public function testImplementorsAreCalled()
    {
        TestFlushable::$flushed = false;

        Injector::inst()->get(Kernel::class)->boot(true);
        $this->get('/');
        $this->assertTrue(TestFlushable::$flushed);

        // reset the kernel Flush flag
        Injector::inst()->get(Kernel::class)->boot();
    }
}
