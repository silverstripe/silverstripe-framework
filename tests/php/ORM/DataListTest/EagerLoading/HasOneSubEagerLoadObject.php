<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class HasOneSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'HasOneSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'HasOneSubSubEagerLoadObject' => HasOneSubSubEagerLoadObject::class
    ];

    private static $belongs_to = [
        'HasOneEagerLoadObject' => HasOneEagerLoadObject::class
    ];
}
