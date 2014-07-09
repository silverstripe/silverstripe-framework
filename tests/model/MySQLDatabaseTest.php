<?php
/**
 * @package framework
 * @subpackage testing
 */

class MySQLDatabaseTest extends SapphireTest {
	protected $extraDataObjects = array(
		'MySQLDatabaseTest_DO',
	);
	
	public function setUp() {
		if(DB::get_conn() instanceof MySQLDatabase) {
			MySQLDatabaseTest_DO::config()->db = array(
				'MultiEnum1' => 'MultiEnum("A, B, C, D","")',
				'MultiEnum2' => 'MultiEnum("A, B, C, D","A")',
				'MultiEnum3' => 'MultiEnum("A, B, C, D","A, B")',
			);
		}
		$this->markTestSkipped('This test requires the Config API to be immutable');
		parent::setUp();
	}
		
	/**
	 * Check that once a schema has been generated, then it doesn't need any more updating
	 */
	public function testFieldsDontRerequestChanges() {
		// These are MySQL specific :-S
		if(DB::get_conn() instanceof MySQLDatabase) {
			$schema = DB::get_schema();
			$test = $this;
			DB::quiet();
		
			// Verify that it doesn't need to be recreated
			$schema->schemaUpdate(function() use ($test, $schema) {
				$obj = new MySQLDatabaseTest_DO();
				$obj->requireTable();
				$needsUpdating = $schema->doesSchemaNeedUpdating();
				$schema->cancelSchemaUpdate();

				$test->assertFalse($needsUpdating);
			});
		}
	}
}

class MySQLDatabaseTest_DO extends DataObject implements TestOnly {
	private static $db = array();

}
