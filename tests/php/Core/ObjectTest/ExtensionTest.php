<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class ExtensionTest extends Object
{
    private static $extensions = array(
        'SilverStripe\\Core\\Tests\\oBjEcTTEST\\EXTENDTest1',
        "SilverStripe\\Core\\Tests\\ObjectTest\\ExtendTest2('FOO', 'BAR')",
    );
}
