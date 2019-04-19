<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @property string $Title
 * @method TestObject Parent()
 * @method Item Child()
 */
class PseudoPolyJoinObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_PseudoPolyJoinObject';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Parent' => TestObject::class,
        'Child' => Item::class,
    ];

    private static $default_sort = '"Sort" ASC';
}
