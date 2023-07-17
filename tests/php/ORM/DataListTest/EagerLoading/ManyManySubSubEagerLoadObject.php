<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManySubSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManySubSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $belongs_many_many = [
        'ManyManyEagerLoadObjects' => ManyManyEagerLoadObjects::class,
        'ManyManySubEagerLoadObjects' => ManyManySubEagerLoadObject::class
    ];
}
