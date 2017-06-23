<?php

namespace SilverStripe\Control\Tests\FlushMiddlewareTest;

use SilverStripe\Core\Flushable;
use SilverStripe\Dev\TestOnly;

class TestFlushable implements Flushable, TestOnly
{
    public static $flushed = false;

    public static function flush()
    {
        self::$flushed = true;
    }
}
