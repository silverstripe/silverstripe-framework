<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtensionRemoveTest extends BaseObject
{
    private static $extensions = array(
        ExtendTest1::class,
    );
}
