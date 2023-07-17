<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class HasManySubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'HasManySubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'HasManyEagerLoadObject' => HasManyEagerLoadObject::class
    ];

    private static $has_many = [
        'HasManySubSubEagerLoadObjects' => HasManySubSubEagerLoadObject::class
    ];
}
