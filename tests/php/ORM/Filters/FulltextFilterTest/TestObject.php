<?php

namespace SilverStripe\ORM\Tests\Filters\FulltextFilterTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{

	private static $table_name = 'FulltextFilterTest_DataObject';

	private static $db = array(
		"ColumnA" => "Varchar(255)",
		"ColumnB" => "HTMLText",
		"ColumnC" => "Varchar(255)",
		"ColumnD" => "HTMLText",
		"ColumnE" => 'Varchar(255)'
	);

	private static $indexes = array(
		'SearchFields' => array(
			'type' => 'fulltext',
			'name' => 'SearchFields',
			'value' => '"ColumnA", "ColumnB"',
		),
		'OtherSearchFields' => 'fulltext ("ColumnC", "ColumnD")',
		'SingleIndex' => 'fulltext ("ColumnE")'
	);

	private static $create_table_options = array(
		MySQLSchemaManager::ID => "ENGINE=MyISAM",
	);

}
