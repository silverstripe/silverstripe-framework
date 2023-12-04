<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectHasMultiRelationalHasMany extends DataObject implements TestOnly
{
    private static string $table_name = 'DataQueryTest_ObjectHasMultiRelationalHasMany';

    private static array $db = [
        'Name' => 'Varchar',
        'SortOrder' => 'Int',
    ];

    private static array $has_many = [
        'MultiRelational1' => ObjectHasMultiRelationalHasOne::class . '.MultiRelational',
        'MultiRelational2' => ObjectHasMultiRelationalHasOne::class . '.MultiRelational',
    ];
}
