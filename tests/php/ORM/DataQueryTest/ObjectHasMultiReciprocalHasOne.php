<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

class ObjectHasMultiRelationalHasOne extends DataObject implements TestOnly
{
    private static string $table_name = 'DataQueryTest_ObjectHasMultiRelationalHasOne';

    private static array $db = [
        'Name' => 'Varchar',
        'SortOrder' => 'Int',
    ];

    private static array $has_one = [
        'MultiRelational' => [
            'class' => DataObject::class,
            DataObjectSchema::HAS_ONE_MULTI_RELATIONAL => true,
        ],
    ];
}
