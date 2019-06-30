<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class DefinesFooAndBar extends DefinesFoo
{
    protected static $foo = 3;
    public static $bar = 3;
}
