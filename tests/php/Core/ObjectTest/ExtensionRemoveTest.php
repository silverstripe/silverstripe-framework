<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class ExtensionRemoveTest extends Object
{
    private static $extensions = array(
        ExtendTest1::class,
    );
}
