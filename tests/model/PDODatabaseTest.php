<?php
/**
 * @package framework
 * @subpackage testing
 */

class PDODatabaseTest extends SapphireTest {
	
	protected static $fixture_file = 'MySQLDatabaseTest.yml';

	protected $extraDataObjects = array(
		'MySQLDatabaseTest_Data'
	);

	public function testPreparedStatements() {
		if(!(DB::get_connector() instanceof PDOConnector)) {
			$this->markTestSkipped('This test requires the current DB connector is PDO');
		}

		// Test preparation of equivalent statemetns
		$result1 = DB::get_connector()->preparedQuery(
			'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
			array(0)
		);

		$result2 = DB::get_connector()->preparedQuery(
			'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
			array(2)
		);
		$this->assertInstanceOf('PDOQuery', $result1);
		$this->assertInstanceOf('PDOQuery', $result2);

		// Also select non-prepared statement
		$result3 = DB::get_connector()->query('SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" ORDER BY "Sort"');
		$this->assertInstanceOf('PDOQuery', $result3);

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

		// Test first
		$this->assertEquals(
			array(
				'Sort' => 1,
				'Title' => 'First Item'
			),
			$result1->first()
		);

		// Test seek
		$this->assertEquals(
			array(
				'Sort' => 2,
				'Title' => 'Second Item'
			),
			$result1->seek(1)
		);

		// Test count
		$this->assertEquals(4, $result1->numRecords());

		// Test second statement
		$this->assertEquals(
			array(
				'Sort' => 3,
				'Title' => 'Third Item'
			),
			$result2->next()
		);

		// Test non-prepared query
		$this->assertEquals(
			array(
				'Sort' => 1,
				'Title' => 'First Item'
			),
			$result3->next()
		);
	}

	public function testAffectedRows() {
		if(!(DB::get_connector() instanceof PDOConnector)) {
			$this->markTestSkipped('This test requires the current DB connector is PDO');
		}

		$query = new SQLUpdate('MySQLDatabaseTest_Data');
		$query->setAssignments(array('Title' => 'New Title'));

		// Test update which affects no rows
		$query->setWhere(array('Title' => 'Bob'));
		$result = $query->execute();
		$this->assertInstanceOf('PDOQuery', $result);
		$this->assertEquals(0, DB::affected_rows());

		// Test update which affects some rows
		$query->setWhere(array('Title' => 'First Item'));
		$result = $query->execute();
		$this->assertInstanceOf('PDOQuery', $result);
		$this->assertEquals(1, DB::affected_rows());
	}
}