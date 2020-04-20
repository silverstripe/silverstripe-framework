<?php

namespace SilverStripe\ORM\Tests\UnsavedRelationListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;

/**
 * @method HasManyList RelatedObjects()
 * @method HasManyList Children()
 * @method ManyManyList Siblings()
 */
class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'UnsavedRelationListTest_DataObject';

    private static $db = [
        'Name' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => TestObject::class,
        'RelatedObject' => DataObject::class
    ];

    private static $has_many = [
        'Children' => 'SilverStripe\\ORM\\Tests\\UnsavedRelationListTest\\TestObject.Parent',
        'RelatedObjects' => 'SilverStripe\\ORM\\Tests\\UnsavedRelationListTest\\TestObject.RelatedObject'
    ];

    private static $many_many = [
        'Siblings' => TestObject::class,
    ];

    private static $many_many_extraFields = [
        'Siblings' => [
            'Number' => 'Int',
        ],
    ];
}
