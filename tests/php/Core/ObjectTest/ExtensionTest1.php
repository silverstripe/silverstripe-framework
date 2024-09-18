<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtensionTest1 extends BaseObject
{
    private static $extensions = [
        'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
        "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2('FOO', 'BAR')",
    ];
}
