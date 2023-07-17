<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class EagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'EagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'HasOneEagerLoadObject' => HasOneEagerLoadObject::class
    ];

    private static $belongs_to = [
        'BelongsToEagerLoadObject' => BelongsToEagerLoadObject::class
    ];

    private static $has_many = [
        'HasManyEagerLoadObjects' => HasManyEagerLoadObject::class
    ];

    private static $many_many = [
        'ManyManyEagerLoadObjects' => ManyManyEagerLoadObject::class,
        'ManyManyThroughEagerLoadObjects' => [
            'through' => EagerLoadObjectManyManyThroughEagerLoadObject::class,
            'from' => 'EagerLoadObject',
            'to' => 'ManyManyThroughEagerLoadObject',
        ],
        'MixedManyManyEagerLoadObjects' => MixedManyManyEagerLoadObject::class,
        'ManyManyEagerLoadWithExtraFields' => ManyManyEagerLoadObject::class,
    ];

    private static $many_many_extraFields = [
        'ManyManyEagerLoadWithExtraFields' => [
            'SomeText' => 'Varchar',
            'SomeBool' => 'Boolean',
            'SomeInt' => 'Int',
        ],
    ];

    private static $belongs_many_many = [
        'BelongsManyManyEagerLoadObjects' => BelongsManyManyEagerLoadObject::class,
    ];
}
