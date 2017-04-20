<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class ExtensionTest2 extends Object
{
    private static $extensions = [
        TestExtension::class,
    ];
}
