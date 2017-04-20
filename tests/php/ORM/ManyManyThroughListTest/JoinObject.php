<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @property string $Title
 * @method TestObject Parent()
 * @method Item Child()
 */
class JoinObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_JoinObject';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Parent' => TestObject::class,
        'Child' => Item::class,
    ];
}
