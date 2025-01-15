<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class UniqueIndexObject extends DataObject implements TestOnly
{
    private static string $table_name = 'DataObjectTest_UniqueIndexObject';

    private static array $db = [
        'SingleField' => 'Varchar',
        'Name' => 'Varchar',
        'Code' => 'Varchar',
    ];

    private static array $indexes = [
        'SingleFieldIndex' => [
            'type' => 'unique',
            'columns' => [
                'SingleField',
            ],
        ],
        'MultiFieldIndex' => [
            'type' => 'unique',
            'columns' => [
                'Name',
                'Code',
            ],
        ],
    ];
}
