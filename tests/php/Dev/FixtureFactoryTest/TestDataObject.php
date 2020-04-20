<?php

namespace SilverStripe\Dev\Tests\FixtureFactoryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{
    private static $db = [
        "Name" => "Varchar"
    ];

    private static $table_name = 'FixtureFactoryTest_TestDataObject';

    private static $has_many = [
        "HasManyRelation" => DataObjectRelation::class
    ];

    private static $many_many = [
        "ManyManyRelation" => DataObjectRelation::class
    ];

    private static $many_many_extraFields = [
        "ManyManyRelation" => [
            "Label" => "Varchar"
        ]
    ];
}
