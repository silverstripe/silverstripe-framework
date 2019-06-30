<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class Second extends First
{
    private static $first = array('test_2');
}
