<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\TestOnly;

class Combined1 extends Config implements TestOnly
{
    /**
     * @config
     */
    private static $first = array('test_1');
    /**
     * @config
     */
    private static $second = array('test_1');
}
