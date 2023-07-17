<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class BelongsManyManySubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'BelongsManyManySubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        // note: need to use singular name for relationship rather than plural otherwise
        // will get error get
        // mysqli_sql_exception: Incorrect table name 'BelongsManyManySubEagerLoadObject_BelongsManyManyEagerLoadObjects'
        'BelongsManyManyEagerLoadObject' => BelongsManyManyEagerLoadObject::class
    ];

    private static $belongs_many_many = [
        'BelongsManyManySubSubEagerLoadObjects' => BelongsManyManySubSubEagerLoadObject::class
    ];
}
