<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtensionTest2 extends BaseObject
{
    private static $extensions = [
        TestExtension::class,
    ];
}
