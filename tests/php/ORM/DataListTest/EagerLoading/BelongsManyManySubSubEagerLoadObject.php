<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class BelongsManyManySubSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'BelongsManyManySubSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        // note: using 'MyManyMany' as name to otherise will get
        // mysqli_sql_exception: Incorrect table name'BelongsManyManySubSubEagerLoadObject_BelongsManyManySubEagerLoadObjects'
        // or 'BelongsManyManySubSubEagerLoadObject_BelongsManyManySubEagerLoadObjects'
        // for relationships named BelongsManyManySubEagerLoadObjects (plural) and
        // BelongsManyManySubEagerLoadObject (singular)
        'MyManyMany' => BelongsManyManySubEagerLoadObject::class
    ];
}
