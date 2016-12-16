<?php

namespace SilverStripe\ORM\Tests\SQLSelectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'SQLSelectTest_DO';

    private static $db = array(
        "Name" => "Varchar",
        "Meta" => "Varchar",
        "Common" => "Varchar",
        "Date" => "Datetime"
    );
}
