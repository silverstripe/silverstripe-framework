<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\TestOnly;

class First extends Config implements TestOnly
{
    /**
     * @config
     */
    private static $first = array('test_1');
    /**
     * @config
     */
    private static $second = array('test_1');
    /**
     * @config
     */
    private static $third = 'test_1';

    /**
     * @config
     */
    private static $bool = true;
    /**
     * @config
     */
    private static $int = 42;
    /**
     * @config
     */
    private static $string = 'value';
    /**
     * @config
     */
    private static $nullable = 'value';

    /**
     * @config
     */
    private static $default_false = false;
    /**
     * @config
     */
    private static $default_null = null;
    /**
     * @config
     */
    private static $default_zero = 0;
    public static $default_emtpy_string = '';
}
