<?php

namespace SilverStripe\Forms\Tests\DBFileTest;

use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Dev\TestOnly;

/**
 * @property DBFile $AnotherFile
 */
class Subclass extends TestObject implements TestOnly
{
    private static $table_name = 'DBFileTest_Subclass';

    private static $db = array(
        "AnotherFile" => "DBFile"
    );
}
