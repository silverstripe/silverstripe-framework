<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * @property string $Title
 * @method ManyManyThroughList Objects()
 */
class Item extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughListTest_Item';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $belongs_many_many = [
        'Objects' => 'SilverStripe\\ORM\\Tests\\ManyManyThroughListTest\\TestObject.Items'
    ];
}
