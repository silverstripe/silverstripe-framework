<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class HasManySubSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'HasManySubSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'HasManySubEagerLoadObject' => HasManySubEagerLoadObject::class
    ];

    private static $has_many = [
        'HasManySubSubSubEagerLoadObjects' => HasManySubSubSubEagerLoadObject::class
    ];
}
