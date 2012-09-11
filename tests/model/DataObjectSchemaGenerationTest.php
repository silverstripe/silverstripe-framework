<?php

class DataObjectSchemaGenerationTest extends SapphireTest {
	protected $extraDataObjects = array(
		'DataObjectSchemaGenerationTest_DO',
	);

	/**
	 * Check that once a schema has been generated, then it doesn't need any more updating
	 */
	function testFieldsDontRerequestChanges() {
		$db = DB::getConn();
		DB::quiet();


		// Table will have been initially created by the $extraDataObjects setting
		// Verify that it doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_DO();
		$obj->requireTable();
		$db->endSchemaUpdate();
		
		// Test table within this database
		$db->beginSchemaUpdate();
		$obj2 = new DataObjectSchemaGenerationTest_DO();
		$obj2->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();

		$this->assertFalse($needsUpdating);
	}

	/**
	 * Check that updates to a class fields are reflected in the database
	 */
	function testFieldsRequestChanges() {
		$db = DB::getConn();
		DB::quiet();


		// Table will have been initially created by the $extraDataObjects setting
		// Verify that it doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_DO();
		$obj->requireTable();
		$db->endSchemaUpdate();
		
		// Let's insert a new field here
		DataObjectSchemaGenerationTest_DO::$db['SecretField'] = 'Varchar(100)';
		
		// Test table within this database
		$db->beginSchemaUpdate();
		$obj2 = new DataObjectSchemaGenerationTest_DO();
		$obj2->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();

		$this->assertTrue($needsUpdating);
	}
	
	/**
	 * Check that indexes on a newly generated class do not subsequently request modification 
	 */
	function testIndexesDontRerequestChanges() {
		$db = DB::getConn();
		DB::quiet();
		
		// enable fulltext option on this table
		Config::inst()->update('DataObjectSchemaGenerationTest_IndexDO', 'create_table_options', array('MySQLDatabase' => 'ENGINE=MyISAM'));
		
		// Table will have been initially created by the $extraDataObjects setting
		// Verify that it doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_IndexDO();
		$obj->requireTable();
		$db->endSchemaUpdate();
		
		// Test table within this database
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
	function testIndexesRerequestChanges() {
		$db = DB::getConn();
		DB::quiet();
		
		// enable fulltext option on this table
		Config::inst()->update('DataObjectSchemaGenerationTest_IndexDO', 'create_table_options', array('MySQLDatabase' => 'ENGINE=MyISAM'));
		
		// Table will have been initially created by the $extraDataObjects setting
		// Verify that it doesn't need to be recreated
		$db->beginSchemaUpdate();
		$obj = new DataObjectSchemaGenerationTest_IndexDO();
		$obj->requireTable();
		$db->endSchemaUpdate();
		
		// Let's insert a new field here
		DataObjectSchemaGenerationTest_IndexDO::$indexes['SearchFields']['value'] = '"Title"';
		
		// Test table within this database
		$db->beginSchemaUpdate();
		$obj2 = new DataObjectSchemaGenerationTest_IndexDO();
		$obj2->requireTable();
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();

		$this->assertTrue($needsUpdating);
	}

}

class DataObjectSchemaGenerationTest_DO extends DataObject implements TestOnly {
	static $db = array(
		'Enum1' => 'Enum("A, B, C, D","")',
		'Enum2' => 'Enum("A, B, C, D","A")',
	);
}


class DataObjectSchemaGenerationTest_IndexDO extends DataObjectSchemaGenerationTest_DO implements TestOnly {
	static $db = array(
		'Title' => 'Varchar(255)',
		'Content' => 'Text'
	);

	static $indexes = array(
		// Space between 'unique' and '("Name")' is critical. @todo - Robustify?
		'NameIndex' => 'unique ("Title")', 
		'SearchFields' => array(
			'type' => 'fulltext',
			'name' => 'SearchFields',
			'value' => '"Title","Content"'
		)
	);

}
