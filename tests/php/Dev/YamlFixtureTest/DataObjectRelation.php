<?php

namespace SilverStripe\Dev\Tests\YamlFixtureTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DataObjectRelation extends DataObject implements TestOnly
{
    private static $table_name = 'YamlFixtureTest_DataObjectRelation';

    private static $db = array(
        "Name" => "Varchar"
    );
    private static $belongs_many_many = array(
        "TestParent" => TestDataObject::class
    );
}
