<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class Third extends Second
{
    private static $first = ['test_3'];
    private static $second = ['test_3'];
    public static $fourth = ['test_3'];
}
