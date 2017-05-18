<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest;

use SilverStripe\Dev\TestOnly;

class TestIndexObject extends TestObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaGenerationTest_IndexDO';
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
    ];

    private static $indexes = [
        'NameIndex' => [
            'type' => 'unique',
            'columns' => ['Title'],
        ],
        'SearchFields' => [
            'type' => 'fulltext',
            'name' => 'SearchFields',
            'columns' => ['Title', 'Content'],
        ],
    ];

    /**
     * @config
     */
    private static $indexes_alt = [
        'NameIndex' => [
            'type' => 'unique',
            'name' => 'NameIndex',
            'columns' => ['Title'],
        ],
        'SearchFields' => [
            'type' => 'fulltext',
            'columns' => ['Title', 'Content'],
        ],
    ];
}
