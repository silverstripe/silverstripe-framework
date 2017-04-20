<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectC extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_C';

    private static $db = array(
        'Title' => 'Varchar'
    );

    private static $has_one = array(
        'TestA' => ObjectA::class,
        'TestB' => ObjectB::class,
    );

    private static $has_many = array(
        'TestAs' => ObjectA::class,
        'TestBs' => 'SilverStripe\\ORM\\Tests\\DataQueryTest\\ObjectB.TestC',
        'TestBsTwo' => 'SilverStripe\\ORM\\Tests\\DataQueryTest\\ObjectB.TestCTwo',
    );

    private static $many_many = array(
        'ManyTestAs' => ObjectA::class,
        'ManyTestBs' => ObjectB::class,
    );
}
