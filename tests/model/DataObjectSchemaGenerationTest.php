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
		$needsUpdating = $db->doesSchemaNeedUpdating();
		$db->cancelSchemaUpdate();
		
		$this->assertFalse($needsUpdating);
	}
}

class DataObjectSchemaGenerationTest_DO extends DataObject implements TestOnly {
	static $db = array(
		'Enum1' => 'Enum("A, B, C, D","")',
		'Enum2' => 'Enum("A, B, C, D","A")',
	);

}
