<?php

namespace SilverStripe\Dev\Tests\FixtureFactoryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DataObjectRelation extends DataObject implements TestOnly
{
    private static $db = array(
        "Name" => "Varchar"
    );

    private static $table_name = 'FixtureFactoryTest_DataObjectRelation';

    private static $belongs_many_many = array(
        "TestParent" => TestDataObject::class
    );

    private static $has_one = array(
        'MyParent' => TestDataObject::class
    );
}
