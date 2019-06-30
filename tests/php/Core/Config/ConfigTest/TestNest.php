<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class TestNest extends BaseObject
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
