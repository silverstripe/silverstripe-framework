<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Object;
use SilverStripe\Dev\TestOnly;

class DefinesFoo extends Object implements TestOnly
{
    protected static $foo = 1;

    private static $not_foo = 1;
}
