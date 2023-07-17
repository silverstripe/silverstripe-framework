<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManySubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManySubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'ManyManySubSubEagerLoadObjects' => ManyManySubSubEagerLoadObject::class
    ];

    private static $belongs_many_many = [
        'EagerLoadObjects' => EagerLoadObject::class,
        'ManyManyEagerLoadObjects' => ManyManyEagerLoadObject::class
    ];
}
