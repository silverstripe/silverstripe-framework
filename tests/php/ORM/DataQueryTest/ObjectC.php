<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectC extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_C';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'TestA' => ObjectA::class,
        'TestB' => ObjectB::class,
    ];

    private static $has_many = [
        'TestAs' => ObjectA::class,
        'TestBs' => 'SilverStripe\\ORM\\Tests\\DataQueryTest\\ObjectB.TestC',
        'TestBsTwo' => 'SilverStripe\\ORM\\Tests\\DataQueryTest\\ObjectB.TestCTwo',
    ];

    private static $many_many = [
        'ManyTestAs' => ObjectA::class,
        'ManyTestBs' => ObjectB::class,
    ];
}
