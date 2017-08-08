<?php

namespace SilverStripe\ORM\Tests\CascadeDeleteTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class GrandChildObject extends DataObject implements TestOnly
{
    private static $table_name = 'CascadeDeleteTest_GrandChildObject';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $belongs_many_many = [
        'Parents' => ChildObject::class,
    ];
}
