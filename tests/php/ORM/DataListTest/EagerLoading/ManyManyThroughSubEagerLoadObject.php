<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManyThroughSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'ManyManyThroughSubSubEagerLoadObjects' => [
            'through' => ManyManyThroughSubEagerLoadObjectManyManyThroughSubSubEagerLoadObject::class,
            'from' => 'ManyManyThroughSubEagerLoadObject',
            'to' => 'ManyManyThroughSubSubEagerLoadObject',
        ]
    ];

    private static $belongs_many_many = [
        'ManyManyThroughEagerLoadObjects' => ManyManyThroughEagerLoadObject::class
    ];
}
