<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Dev\TestOnly;

class DefinesFooDoesntExtendObject implements TestOnly
{
    protected static $foo = 4;
}
