<?php


/**
 * Tests for {@see SQLInsert}
 *
 * @package framework
 * @subpackage tests
 */
class SQLInsertTest extends SapphireTest {

	protected $extraDataObjects = array(
		'SQLInsertTestBase'
	);

	public function testEmptyQueryReturnsNothing() {
		$query = new SQLInsert();
		$this->assertSQLEquals('', $query->sql($parameters));
	}

	public function testBasicInsert() {
		$query = SQLInsert::create()
				->setInto('"SQLInsertTestBase"')
				->assign('"Title"', 'My Object')
				->assign('"HasFun"', 1)
				->assign('"Age"', 10)
				->assign('"Description"', 'No description');
		$sql = $query->sql($parameters);
		// Only test this case if using the default query builder
		if(get_class(DB::get_conn()->getQueryBuilder()) === 'DBQueryBuilder') {
			$this->assertSQLEquals(
				'INSERT INTO "SQLInsertTestBase" ("Title", "HasFun", "Age", "Description") VALUES (?, ?, ?, ?)',
				$sql
			);
		}
		$this->assertEquals(array('My Object', 1, 10, 'No description'), $parameters);
		$query->execute();
		$this->assertEquals(1, DB::affected_rows());

		// Check inserted object is correct
		$firstObject = DataObject::get_one('SQLInsertTestBase', array('"Title"' => 'My Object'), false);
		$this->assertNotEmpty($firstObject);
		$this->assertEquals($firstObject->Title, 'My Object');
		$this->assertNotEmpty($firstObject->HasFun);
		$this->assertEquals($firstObject->Age, 10);
		$this->assertEquals($firstObject->Description, 'No description');
	}

	public function testMultipleRowInsert() {
		$query = SQLInsert::create('"SQLInsertTestBase"');
		$query->addRow(array(
			'"Title"' => 'First Object',
			'"Age"' => 10, // Can't insert non-null values into only one row in a multi-row insert
			'"Description"' => 'First the worst' // Nullable field, can be present in some rows
		));
		$query->addRow(array(
			'"Title"' => 'Second object',
			'"Age"' => 12
		));
		$sql = $query->sql($parameters);
		// Only test this case if using the default query builder
		if(get_class(DB::get_conn()->getQueryBuilder()) === 'DBQueryBuilder') {
			$this->assertSQLEquals(
				'INSERT INTO "SQLInsertTestBase" ("Title", "Age", "Description") VALUES (?, ?, ?), (?, ?, ?)',
				$sql
			);
		}
		$this->assertEquals(array('First Object', 10, 'First the worst', 'Second object', 12, null), $parameters);
		$query->execute();
		$this->assertEquals(2, DB::affected_rows());

		// Check inserted objects are correct
		$firstObject = DataObject::get_one('SQLInsertTestBase', array('"Title"' => 'First Object'), false);
		$this->assertNotEmpty($firstObject);
		$this->assertEquals($firstObject->Title, 'First Object');
		$this->assertEquals($firstObject->Age, 10);
		$this->assertEquals($firstObject->Description, 'First the worst');

		$secondObject = DataObject::get_one('SQLInsertTestBase', array('"Title"' => 'Second object'), false);
		$this->assertNotEmpty($secondObject);
		$this->assertEquals($secondObject->Title, 'Second object');
		$this->assertEquals($secondObject->Age, 12);
		$this->assertEmpty($secondObject->Description);
	}
}

class SQLInsertTestBase extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar(255)',
		'HasFun' => 'Boolean',
		'Age' => 'Int',
		'Description' => 'Text',
	);
}
