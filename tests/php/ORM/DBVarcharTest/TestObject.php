<?php

namespace SilverStripe\ORM\Tests\DBVarcharTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'DBVarcharTest_TestObject';

    private static $db = [
        'Title' => 'Varchar(129)',
        'NullableField' => 'Varchar(111, ["nullifyEmpty" => false])'
    ];
}
