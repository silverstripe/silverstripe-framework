<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Dev\TestOnly;

class DefinesFooDoesntExtendObject implements TestOnly
{
    protected static $foo = 4;
}
