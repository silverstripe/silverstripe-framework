<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

class DefinesFoo extends BaseObject
{
    protected static $foo = 1;

    private static $not_foo = 1;
}
