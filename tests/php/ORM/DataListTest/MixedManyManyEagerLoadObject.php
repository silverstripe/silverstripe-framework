<?php

namespace SilverStripe\ORM\Tests\DataListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class MixedManyManyEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'MixedManyManyEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_many = [
        'MixedHasManyEagerLoadObjects' => MixedHasManyEagerLoadObject::class
    ];

    private static $belongs_many_many = [
        'EagerLoadObjects' => EagerLoadObject::class,
    ];
}
