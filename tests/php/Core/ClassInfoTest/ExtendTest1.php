<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

class ExtendTest1 extends BaseObject
{
    private static $extensions = [
        ExtensionTest1::class,
    ];
}
