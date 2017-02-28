<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Dev\TestOnly;

class Combined1 implements TestOnly
{
    use Configurable;

    /**
     * @config
     */
    private static $first = ['test_1'];

    /**
     * @config
     */
    private static $second = ['test_1'];
}
