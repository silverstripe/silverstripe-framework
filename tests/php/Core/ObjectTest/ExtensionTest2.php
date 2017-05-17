<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtensionTest2 extends BaseObject
{
    private static $extensions = [
        TestExtension::class,
    ];
}
