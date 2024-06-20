<?php

namespace SilverStripe\ORM\Tests\SQLSelectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CteRecursiveObject extends DataObject implements TestOnly
{
    private static $table_name = 'SQLSelectTestCteRecursive';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => CteRecursiveObject::class,
    ];

    private static $has_many = [
        'Children' => CteRecursiveObject::class . '.Parent',
    ];
}
