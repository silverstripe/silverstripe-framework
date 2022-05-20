<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * @property string $Title
 * @method ManyManyThroughList Items()
 */
class PolyObjectA extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_PolyObjectA';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'Items' => [
            'through' => PolyJoinObject::class,
            'from' => 'Parent',
            'to' => 'Child',
        ]
    ];
}
