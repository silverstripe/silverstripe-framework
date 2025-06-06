<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectJ extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_J';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'TestK' => ObjectK::class,
    ];

    private static $has_many = [
        'TestKs' => ObjectK::class,
    ];

    private static $many_many = [
        'ManyTestKs' => ObjectK::class,
    ];
}
