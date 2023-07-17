<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class BelongsToSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'BelongsToSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'BelongsToEagerLoadObject' => BelongsToEagerLoadObject::class
    ];

    private static $belongs_to = [
        'BelongsToSubSubEagerLoadObject' => BelongsToSubSubEagerLoadObject::class
    ];
}
