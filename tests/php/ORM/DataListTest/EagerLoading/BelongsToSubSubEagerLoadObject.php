<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class BelongsToSubSubEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'BelongsToSubSubEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'BelongsToSubEagerLoadObject' => BelongsToSubEagerLoadObject::class
    ];
}
