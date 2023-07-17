<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManyThroughEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'ManyManyThroughSubEagerLoadObjects' => [
            'through' => ManyManyThroughEagerLoadObjectManyManyThroughSubEagerLoadObject::class,
            'from' => 'ManyManyThroughEagerLoadObject',
            'to' => 'ManyManyThroughSubEagerLoadObject',
        ]
    ];

    private static $belongs_many_many = [
        'EagerLoadObjects' => EagerLoadObject::class
    ];
}
