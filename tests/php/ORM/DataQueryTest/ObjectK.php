<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectK extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_K';

    private static $db = [
        'Name' => 'Varchar',
    ];

    private static $has_one = [
        'TestJ' => ObjectJ::class,
    ];
}
