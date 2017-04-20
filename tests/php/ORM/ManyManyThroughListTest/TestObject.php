<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * Basic parent object
 *
 * @property string $Title
 * @method   ManyManyThroughList Items()
 */
class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_Object';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'Items' => [
            'through' => JoinObject::class,
            'from' => 'Parent',
            'to' => 'Child',
        ]
    ];
}
