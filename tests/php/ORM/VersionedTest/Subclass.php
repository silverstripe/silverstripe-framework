<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;

class Subclass extends TestObject implements TestOnly
{
    private static $table_name = 'VersionedTest_Subclass';
    private static $db = array(
        "ExtraField" => "Varchar",
    );
}
