<?php

namespace SilverStripe\Dev\Tests\YamlFixtureTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{
    private static $table_name = 'YamlFixtureTest_TestDataObject';

    private static $db = [
        "Name" => "Varchar"
    ];

    private static $many_many = [
        "ManyManyRelation" => DataObjectRelation::class
    ];
}
