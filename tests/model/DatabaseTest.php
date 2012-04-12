<?php
/**
 * @package framework
 * @subpackage Testing
 */
class DatabaseTest extends SapphireTest {

	protected $extraDataObjects = array(
		'DatabaseTest_MyObject',
	);

	protected $usesDatabase = true;

	function testDontRequireField() {
		$conn = DB::getConn();
		$this->assertArrayHasKey(
			'MyField',
			$conn->fieldList('DatabaseTest_MyObject')
		);

		$conn->dontRequireField('DatabaseTest_MyObject', 'MyField');

		$this->assertArrayHasKey(
			'_obsolete_MyField',
			$conn->fieldList('DatabaseTest_MyObject'),
			'Field is renamed to _obsolete_<fieldname> through dontRequireField()'
		);

		$this->resetDBSchema(true);
	}

	function testRenameField() {
		$conn = DB::getConn();

		$conn->clearCachedFieldlist();

		$conn->renameField('DatabaseTest_MyObject', 'MyField', 'MyRenamedField');

		$this->assertArrayHasKey(
			'MyRenamedField',
			$conn->fieldList('DatabaseTest_MyObject'),
			'New fieldname is set through renameField()'
		);
		$this->assertArrayNotHasKey(
			'MyField',
			$conn->fieldList('DatabaseTest_MyObject'),
			'Old fieldname isnt preserved through renameField()'
		);

		$this->resetDBSchema(true);
	}

	function testMySQLCreateTableOptions() {
		if(DB::getConn() instanceof MySQLDatabase) {
			$ret = DB::query(sprintf(
				'SHOW TABLE STATUS WHERE "Name" = \'%s\'',
				'DatabaseTest_MyObject'
			))->first();
			$this->assertEquals($ret['Engine'],'InnoDB',
				"MySQLDatabase tables can be changed to InnoDB through DataObject::\$create_table_options"
			);
		}
	}

	function testSchemaUpdateChecking() {
		$db = DB::getConn();

		// Initially, no schema changes necessary
		$db->beginSchemaUpdate();
		$this->assertFalse($db->doesSchemaNeedUpdating());

		// If we make a change, then the schema will need updating
		$db->transCreateTable("TestTable");
		$this->assertTrue($db->doesSchemaNeedUpdating());

		// If we make cancel the change, then schema updates are no longer necessary
		$db->cancelSchemaUpdate();
		$this->assertFalse($db->doesSchemaNeedUpdating());
	}

	function testHasTable() {
		$this->assertTrue(DB::getConn()->hasTable('DatabaseTest_MyObject'));
		$this->assertFalse(DB::getConn()->hasTable('asdfasdfasdf'));
	}
	
	function testGetAndReleaseLock() {
		$db = DB::getConn();
		
		if(!$db->supportsLocks()) {
			return $this->markTestSkipped('Tested database doesn\'t support application locks');
		}

		$this->assertTrue($db->getLock('DatabaseTest'), 'Can aquire lock');
		// $this->assertFalse($db->getLock('DatabaseTest'), 'Can\'t repeatedly aquire the same lock');
		$this->assertTrue($db->getLock('DatabaseTest'), 'The same lock can be aquired multiple times in the same connection');

		$this->assertTrue($db->getLock('DatabaseTestOtherLock'), 'Can aquire different lock');
		$db->releaseLock('DatabaseTestOtherLock');
		
		// Release potentially stacked locks from previous getLock() invocations
		$db->releaseLock('DatabaseTest');
		$db->releaseLock('DatabaseTest');
		
		$this->assertTrue($db->getLock('DatabaseTest'), 'Can aquire lock after releasing it');
		$db->releaseLock('DatabaseTest');
	}
	
	function testCanLock() {
		$db = DB::getConn();
		
		if(!$db->supportsLocks()) {
			return $this->markTestSkipped('Database doesn\'t support locks');
		}
		
		if($db instanceof MSSQLDatabase) {
			return $this->markTestSkipped('MSSQLDatabase doesn\'t support inspecting locks');
		}
		
		$this->assertTrue($db->canLock('DatabaseTest'), 'Can lock before first aquiring one');
		$db->getLock('DatabaseTest');
		$this->assertFalse($db->canLock('DatabaseTest'), 'Can\'t lock after aquiring one');
		$db->releaseLock('DatabaseTest');
		$this->assertTrue($db->canLock('DatabaseTest'), 'Can lock again after releasing it');
	}
	
}

class DatabaseTest_MyObject extends DataObject implements TestOnly {

	static $create_table_options = array('MySQLDatabase' => 'ENGINE=InnoDB');

	static $db = array(
		'MyField' => 'Varchar'
	);
}
