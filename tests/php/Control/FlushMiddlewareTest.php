<?php

namespace SilverStripe\Control\Tests;

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
        $this->get('?flush=1');
        $this->assertTrue(TestFlushable::$flushed);
    }
}
