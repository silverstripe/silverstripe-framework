<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtensionTest extends BaseObject
{
    private static $extensions = array(
        'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
        "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2('FOO', 'BAR')",
    );
}
