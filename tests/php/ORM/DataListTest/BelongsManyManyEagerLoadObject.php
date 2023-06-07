<?php

namespace SilverStripe\ORM\Tests\DataListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class BelongsManyManyEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'BelongsManyManyEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'EagerLoadObjects' => EagerLoadObject::class
    ];

    private static $belongs_many_many = [
        'BelongsManyManySubEagerLoadObjects' => BelongsManyManySubEagerLoadObject::class
    ];
}
