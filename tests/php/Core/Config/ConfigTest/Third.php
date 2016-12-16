<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class Third extends Second
{
    private static $first = array('test_3');
    private static $second = array('test_3');
    public static $fourth = array('test_3');
}
