<?php

namespace SilverStripe\Dev\Tests\FixtureFactoryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{
    private static $db = array(
        "Name" => "Varchar"
    );

    private static $table_name = 'FixtureFactoryTest_TestDataObject';

    private static $has_many = array(
        "HasManyRelation" => DataObjectRelation::class
    );

    private static $many_many = array(
        "ManyManyRelation" => DataObjectRelation::class
    );

    private static $many_many_extraFields = array(
        "ManyManyRelation" => array(
            "Label" => "Varchar"
        )
    );
}
