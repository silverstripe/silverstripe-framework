<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtensionRemoveTest1 extends BaseObject
{
    private static $extensions = [
        ExtendTest1::class,
    ];
}
