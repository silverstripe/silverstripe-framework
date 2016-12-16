<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class DefinesFooAndBar extends DefinesFoo
{
    protected static $foo = 3;
    public static $bar = 3;
}
