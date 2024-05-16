<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * Basic parent object
 *
 * @property string $Title
 * @method ManyManyThroughList Items()
 */
class TestObjectSubclass extends TestObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_TestObjectSubclass';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'MoreItems' => [
            'through' => PseudoPolyJoinObject::class,
            'from' => 'Parent',
            'to' => 'Child',
        ]
    ];
}
