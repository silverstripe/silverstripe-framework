<?php

namespace SilverStripe\ORM\Tests\DataListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class EagerLoadObjectManyManyThroughEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'EagerLoadObjectManyManyThroughEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'EagerLoadObject' => EagerLoadObject::class,
        'ManyManyThroughEagerLoadObject' => ManyManyThroughEagerLoadObject::class
    ];
}
