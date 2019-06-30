<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * @property string $Title
 * @method ManyManyThroughList Items()
 */
class PolyObjectB extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_PolyObjectB';

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
