<?php

namespace SilverStripe\ORM\Tests\DataListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class HasOneEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'HasOneEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'HasOneSubEagerLoadObject' => HasOneSubEagerLoadObject::class
    ];

    private static $belongs_to = [
        'EagerLoadObject' => EagerLoadObject::class
    ];
}
