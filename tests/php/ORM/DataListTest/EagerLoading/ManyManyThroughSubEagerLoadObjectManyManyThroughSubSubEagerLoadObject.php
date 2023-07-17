<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManyThroughSubEagerLoadObjectManyManyThroughSubSubEagerLoadObject extends DataObject implements TestOnly
{
    // Removed some of the table name and suffixed _truncated because the table name was too long for MySQL
    private static $table_name = 'ManyManyThroughSubEagerLoadObjectManyManyThroughSubSub_truncated';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'ManyManyThroughSubEagerLoadObject' => ManyManyThroughSubEagerLoadObject::class,
        'ManyManyThroughSubSubEagerLoadObject' => ManyManyThroughSubSubEagerLoadObject::class
    ];
}
