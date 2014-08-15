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

	public function testDontRequireField() {
		$schema = DB::get_schema();
		$this->assertArrayHasKey(
			'MyField',
			$schema->fieldList('DatabaseTest_MyObject')
		);

		$schema->dontRequireField('DatabaseTest_MyObject', 'MyField');

		$this->assertArrayHasKey(
			'_obsolete_MyField',
			$schema->fieldList('DatabaseTest_MyObject'),
			'Field is renamed to _obsolete_<fieldname> through dontRequireField()'
		);

		$this->resetDBSchema(true);
	}

	public function testRenameField() {
		$schema = DB::get_schema();

		$schema->clearCachedFieldlist();

		$schema->renameField('DatabaseTest_MyObject', 'MyField', 'MyRenamedField');

		$this->assertArrayHasKey(
			'MyRenamedField',
			$schema->fieldList('DatabaseTest_MyObject'),
			'New fieldname is set through renameField()'
		);
		$this->assertArrayNotHasKey(
			'MyField',
			$schema->fieldList('DatabaseTest_MyObject'),
			'Old fieldname isnt preserved through renameField()'
		);

		$this->resetDBSchema(true);
	}

	public function testMySQLCreateTableOptions() {
		if(!(DB::get_conn() instanceof MySQLDatabase)) {
			$this->markTestSkipped('MySQL only');
		}


		$ret = DB::query(sprintf(
			'SHOW TABLE STATUS WHERE "Name" = \'%s\'',
			'DatabaseTest_MyObject'
		))->first();
		$this->assertEquals($ret['Engine'],'InnoDB',
			"MySQLDatabase tables can be changed to InnoDB through DataObject::\$create_table_options"
		);
	}

	function testIsSchemaUpdating() {
		$schema = DB::get_schema();

		$this->assertFalse($schema->isSchemaUpdating(), 'Before the transaction the flag is false.');

		// Test complete schema update
		$test = $this;
		$schema->schemaUpdate(function() use ($test, $schema) {
			$test->assertTrue($schema->isSchemaUpdating(), 'During the transaction the flag is true.');
		});
		$this->assertFalse($schema->isSchemaUpdating(), 'After the transaction the flag is false.');

		// Test cancelled schema update
		$schema->schemaUpdate(function() use ($test, $schema) {
			$schema->cancelSchemaUpdate();
			$test->assertFalse($schema->doesSchemaNeedUpdating(), 'After cancelling the transaction the flag is false');
		});
	}

	public function testSchemaUpdateChecking() {
		$schema = DB::get_schema();

		// Initially, no schema changes necessary
		$test = $this;
		$schema->schemaUpdate(function() use ($test, $schema) {
			$test->assertFalse($schema->doesSchemaNeedUpdating());

			// If we make a change, then the schema will need updating
			$schema->transCreateTable("TestTable");
			$test->assertTrue($schema->doesSchemaNeedUpdating());

			// If we make cancel the change, then schema updates are no longer necessary
			$schema->cancelSchemaUpdate();
			$test->assertFalse($schema->doesSchemaNeedUpdating());
		});
	}

	public function testHasTable() {
		$this->assertTrue(DB::get_schema()->hasTable('DatabaseTest_MyObject'));
		$this->assertFalse(DB::get_schema()->hasTable('asdfasdfasdf'));
	}

	public function testGetAndReleaseLock() {
		$db = DB::get_conn();

		if(!$db->supportsLocks()) {
			return $this->markTestSkipped('Tested database doesn\'t support application locks');
		}

		$this->assertTrue($db->getLock('DatabaseTest'),
			'Can aquire lock');
		// $this->assertFalse($db->getLock('DatabaseTest'), 'Can\'t repeatedly aquire the same lock');
		$this->assertTrue($db->getLock('DatabaseTest'),
			'The same lock can be aquired multiple times in the same connection');

		$this->assertTrue($db->getLock('DatabaseTestOtherLock'),
			'Can aquire different lock');
		$db->releaseLock('DatabaseTestOtherLock');

		// Release potentially stacked locks from previous getLock() invocations
		$db->releaseLock('DatabaseTest');
		$db->releaseLock('DatabaseTest');

		$this->assertTrue($db->getLock('DatabaseTest'),
			'Can aquire lock after releasing it');
		$db->releaseLock('DatabaseTest');
	}

	public function testCanLock() {
		$db = DB::get_conn();

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

	private static $create_table_options = array(MySQLSchemaManager::ID => 'ENGINE=InnoDB');

	private static $db = array(
		'MyField' => 'Varchar'
	);
}
