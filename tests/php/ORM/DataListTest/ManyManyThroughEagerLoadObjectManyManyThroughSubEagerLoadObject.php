<?php

namespace SilverStripe\ORM\Tests\DataListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManyThroughEagerLoadObjectManyManyThroughSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughEagerLoadObjectManyManyThroughSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'ManyManyThroughEagerLoadObject' => ManyManyThroughEagerLoadObject::class,
        'ManyManyThroughSubEagerLoadObject' => ManyManyThroughSubEagerLoadObject::class
    ];
}
