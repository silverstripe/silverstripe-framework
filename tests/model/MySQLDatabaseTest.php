<?php
/**
 * @package framework
 * @subpackage testing
 */

class MySQLDatabaseTest extends SapphireTest {
	
	protected static $fixture_file = 'MySQLDatabaseTest.yml';

	protected $extraDataObjects = array(
		'MySQLDatabaseTest_Data'
	);


	public function testPreparedStatements() {
		if(!(DB::get_connector() instanceof MySQLiConnector)) {
			$this->markTestSkipped('This test requires the current DB connector is MySQLi');
		}

		// The latest result should not be buffered immediately
		$result1 = DB::get_connector()->preparedQuery(
			'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
			array(0)
		);
		$this->assertInstanceOf('MySQLStatement', $result1);
		$this->assertFalse($result1->isClosed());

		// Any following query should force prior queries to buffer themselves
		$result2 = DB::get_connector()->preparedQuery(
			'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
			array(2)
		);
		$this->assertInstanceOf('MySQLStatement', $result2);
		$this->assertTrue($result1->isClosed());
		$this->assertFalse($result2->isClosed());

		// Non-prepared statements should also force buffering
		$result3 = DB::get_connector()->query('SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" ORDER BY "Sort"');
		$this->assertInstanceOf('MySQLQuery', $result3);
		$this->assertTrue($result1->isClosed());
		$this->assertTrue($result2->isClosed());

		// Iterating one level should not buffer, but return the right result
		$this->assertEquals(
			array(
				'Sort' => 1,
				'Title' => 'First Item'
			),
			$result1->next()
		);
		$this->assertEquals(
			array(
				'Sort' => 2,
				'Title' => 'Second Item'
			),
			$result1->next()
		);
	}
}

class MySQLDatabaseTest_Data extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar',
		'Description' => 'Text',
		'Enabled' => 'Boolean',
		'Sort' => 'Int'
	);

	private static $default_sort = '"Sort" ASC';
}
