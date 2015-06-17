<?php

class DataObjectSchemaGenerationTest extends SapphireTest {
	protected $extraDataObjects = array(
		'DataObjectSchemaGenerationTest_DO',
		'DataObjectSchemaGenerationTest_IndexDO'
	);

	public function setUpOnce() {

		// enable fulltext option on this table
		Config::inst()->update('DataObjectSchemaGenerationTest_IndexDO', 'create_table_options',
			array('MySQLDatabase' => 'ENGINE=MyISAM'));

		parent::setUpOnce();
	}

	/**
	 * Check that once a schema has been generated, then it doesn't need any more updating
	 */
	public function testFieldsDontRerequestChanges() {
		$db = DB::getConn();
		DB::quiet();

		// Table will have been initially created by the $extraDataObjects setting

		// Verify that it doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_DO();
		$obj->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();
		$this->assertFalse($needsUpdating);
	}

	/**
	 * Check that updates to a class fields are reflected in the database
	 */
	public function testFieldsRequestChanges() {
		$db = DB::getConn();
		DB::quiet();

		// Table will have been initially created by the $extraDataObjects setting

		// Let's insert a new field here
		Config::inst()->update('DataObjectSchemaGenerationTest_DO', 'db', array(
			'SecretField' => 'Varchar(100)'
		));

		// Verify that the above extra field triggered a schema update
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_DO();
		$obj->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();
		$this->assertTrue($needsUpdating);
	}

	/**
	 * Check that indexes on a newly generated class do not subsequently request modification
	 */
	public function testIndexesDontRerequestChanges() {
		$db = DB::getConn();
		DB::quiet();

		// Table will have been initially created by the $extraDataObjects setting

		// Verify that it doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_IndexDO();
		$obj->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();
		$this->assertFalse($needsUpdating);

		// Test with alternate index format, although these indexes are the same
		Config::inst()->remove('DataObjectSchemaGenerationTest_IndexDO', 'indexes');
		Config::inst()->update('DataObjectSchemaGenerationTest_IndexDO', 'indexes',
			Config::inst()->get('DataObjectSchemaGenerationTest_IndexDO', 'indexes_alt')
		);

		// Verify that it still doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj2 = new DataObjectSchemaGenerationTest_IndexDO();
		$obj2->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();
		$this->assertFalse($needsUpdating);
	}

	/**
	 * Check that updates to a dataobject's indexes are reflected in DDL
	 */
	public function testIndexesRerequestChanges() {
		$db = DB::getConn();
		DB::quiet();

		// Table will have been initially created by the $extraDataObjects setting

		// Update the SearchFields index here
		Config::inst()->update('DataObjectSchemaGenerationTest_IndexDO', 'indexes', array(
			'SearchFields' => array(
				'value' => 'Title'
			)
		));

		// Verify that the above index change triggered a schema update
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_IndexDO();
		$obj->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();
		$this->assertTrue($needsUpdating);
	}

	/**
	 * Tests the generation of the ClassName spec and ensure it's not unnecessarily influenced
	 * by the order of classnames of existing records
	 */
	public function testClassNameSpecGeneration() {

		// Test with blank entries
		DataObject::clear_classname_spec_cache();
		$fields = DataObject::database_fields('DataObjectSchemaGenerationTest_DO');
		$this->assertEquals(
			"Enum('DataObjectSchemaGenerationTest_DO, DataObjectSchemaGenerationTest_IndexDO')",
			$fields['ClassName']
		);

		// Test with instance of subclass
		$item1 = new DataObjectSchemaGenerationTest_IndexDO();
		$item1->write();
		DataObject::clear_classname_spec_cache();
		$fields = DataObject::database_fields('DataObjectSchemaGenerationTest_DO');
		$this->assertEquals(
			"Enum('DataObjectSchemaGenerationTest_DO, DataObjectSchemaGenerationTest_IndexDO')",
			$fields['ClassName']
		);
		$item1->delete();

		// Test with instance of main class
		$item2 = new DataObjectSchemaGenerationTest_DO();
		$item2->write();
		DataObject::clear_classname_spec_cache();
		$fields = DataObject::database_fields('DataObjectSchemaGenerationTest_DO');
		$this->assertEquals(
			"Enum('DataObjectSchemaGenerationTest_DO, DataObjectSchemaGenerationTest_IndexDO')",
			$fields['ClassName']
		);
		$item2->delete();

		// Test with instances of both classes
		$item1 = new DataObjectSchemaGenerationTest_IndexDO();
		$item1->write();
		$item2 = new DataObjectSchemaGenerationTest_DO();
		$item2->write();
		DataObject::clear_classname_spec_cache();
		$fields = DataObject::database_fields('DataObjectSchemaGenerationTest_DO');
		$this->assertEquals(
			"Enum('DataObjectSchemaGenerationTest_DO, DataObjectSchemaGenerationTest_IndexDO')",
			$fields['ClassName']
		);
		$item1->delete();
		$item2->delete();
	}
}

class DataObjectSchemaGenerationTest_DO extends DataObject implements TestOnly {
	private static $db = array(
		'Enum1' => 'Enum("A, B, C, D","")',
		'Enum2' => 'Enum("A, B, C, D","A")',
	);
}


class DataObjectSchemaGenerationTest_IndexDO extends DataObjectSchemaGenerationTest_DO implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar(255)',
		'Content' => 'Text'
	);

	private static $indexes = array(
		'NameIndex' => 'unique ("Title")',
		'SearchFields' => array(
			'type' => 'fulltext',
			'name' => 'SearchFields',
			'value' => '"Title","Content"'
		)
	);

	/** @config */
	private static $indexes_alt = array(
		'NameIndex' => array(
			'type' => 'unique',
			'name' => 'NameIndex',
			'value' => '"Title"'
		),
		'SearchFields' => 'fulltext ("Title","Content")'
	);
}
