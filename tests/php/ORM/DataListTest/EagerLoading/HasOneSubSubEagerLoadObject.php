<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class HasOneSubSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'HasOneSubSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $belongs_to = [
        'HasOneSubEagerLoadObject' => HasOneSubEagerLoadObject::class
    ];
}
