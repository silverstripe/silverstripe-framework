<?php

namespace SilverStripe\ORM\Tests\Filters\FulltextFilterTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{

    private static $table_name = 'FulltextFilterTest_DataObject';

    private static $db = [
        'ColumnA' => 'Varchar(255)',
        'ColumnB' => 'HTMLText',
        'ColumnC' => 'Varchar(255)',
        'ColumnD' => 'HTMLText',
        'ColumnE' => 'Varchar(255)',
    ];

    private static $indexes = array(
        'SearchFields' => [
            'type' => 'fulltext',
            'name' => 'SearchFields',
            'columns' => ['ColumnA', 'ColumnB'],
        ],
        'OtherSearchFields' => [
            'type' => 'fulltext',
            'columns' => ['ColumnC', 'ColumnD'],
        ],
        'SingleIndex' => [
            'type' => 'fulltext',
            'columns' => ['ColumnE'],
        ],
    );

    private static $create_table_options = array(
        MySQLSchemaManager::ID => "ENGINE=MyISAM",
    );
}
