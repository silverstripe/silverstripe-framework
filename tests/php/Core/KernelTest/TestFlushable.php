<?php

namespace SilverStripe\Core\Tests\KernelTest;

use SilverStripe\Core\Flushable;
use SilverStripe\Dev\TestOnly;

class TestFlushable implements Flushable, TestOnly
{
    public static $flushed = false;

    public static function flush()
    {
        TestFlushable::$flushed = true;
    }
}
