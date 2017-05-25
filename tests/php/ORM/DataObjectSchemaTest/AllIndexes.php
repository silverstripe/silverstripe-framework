<?php
namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class AllIndexes extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaTest_AllIndexes';

    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'Varchar',
        'Number' => 'Int',
    ];

    private static $indexes = [
        'Content' => true,
        'IndexCols' => ['Title', 'Content'],
        'IndexUnique' => [
            'type' => 'unique',
            'columns' => ['Number'],
        ],
        'IndexNormal' => [
            'columns' => ['Title'],
        ],
    ];
}
