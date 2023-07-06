<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class HasManyEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'HasManyEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'EagerLoadObject' => EagerLoadObject::class
    ];

    private static $has_many = [
        'HasManySubEagerLoadObjects' => HasManySubEagerLoadObject::class
    ];
}
