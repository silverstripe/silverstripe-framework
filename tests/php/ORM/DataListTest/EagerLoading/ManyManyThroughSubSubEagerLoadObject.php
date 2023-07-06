<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ManyManyThroughSubSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughSubSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $belongs_many_many = [
        'ManyManyThroughSubEagerLoadObjects' => ManyManyThroughSubEagerLoadObject::class
    ];
}
