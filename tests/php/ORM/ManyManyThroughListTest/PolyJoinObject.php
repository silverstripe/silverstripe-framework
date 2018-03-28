<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class PolyJoinObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_PolyJoinObject';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Parent' => DataObject::class, // Polymorphic parent
        'Child' => PolyItem::class,
    ];
}
