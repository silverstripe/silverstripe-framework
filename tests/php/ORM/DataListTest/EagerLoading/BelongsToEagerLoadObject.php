<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class BelongsToEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'BelongsToEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'EagerLoadObject' => EagerLoadObject::class
    ];

    private static $belongs_to = [
        'BelongsToSubEagerLoadObject' => BelongsToSubEagerLoadObject::class
    ];
}
