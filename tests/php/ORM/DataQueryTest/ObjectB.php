<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectB extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_B';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'TestC' => ObjectC::class,
        'TestCTwo' => ObjectC::class,
    ];
}
