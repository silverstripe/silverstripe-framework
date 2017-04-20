<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Object;
use SilverStripe\Dev\TestOnly;

class TestNest extends Object implements TestOnly
{
    /**
     * @config
     */
    private static $foo = 3;
    /**
     * @config
     */
    private static $bar = 5;
}
