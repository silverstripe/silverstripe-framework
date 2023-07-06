<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManyEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'ManyManySubEagerLoadObjects' => ManyManySubEagerLoadObject::class
    ];

    private static $belongs_many_many = [
        'EagerLoadObjects' => EagerLoadObject::class
    ];
}
