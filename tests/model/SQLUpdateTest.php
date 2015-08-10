<?php


/**
 * Tests for {@see SQLUpdate}
 *
 * @package framework
 * @subpackage tests
 */
class SQLUpdateTest extends SapphireTest {

	public static $fixture_file = 'SQLUpdateTest.yml';

	protected $extraDataObjects = [
		'SQLUpdateTestBase',
		'SQLUpdateChild'
	];

	public function testEmptyQueryReturnsNothing() {
		$query = new SQLUpdate();
		$this->assertSQLEquals('', $query->sql($parameters));
	}

	public function testBasicUpdate() {
		$query = SQLUpdate::create()
				->setTable('"SQLUpdateTestBase"')
				->assign('"Description"', 'Description 1a')
				->addWhere(['"Title" = ?' => 'Object 1']);
		$sql = $query->sql($parameters);

		// Check SQL
		$this->assertSQLEquals('UPDATE "SQLUpdateTestBase" SET "Description" = ? WHERE ("Title" = ?)', $sql);
		$this->assertEquals(['Description 1a', 'Object 1'], $parameters);

		// Check affected rows
		$query->execute();
		$this->assertEquals(1, DB::affected_rows());

		// Check item updated
		$item = DataObject::get_one('SQLUpdateTestBase', ['"Title"' => 'Object 1']);
		$this->assertEquals('Description 1a', $item->Description);
	}
}

class SQLUpdateTestBase extends DataObject implements TestOnly {
	private static $db = [
		'Title' => 'Varchar(255)',
		'Description' => 'Text'
	];
}

class SQLUpdateChild extends SQLUpdateTestBase {
	private static $db = [
		'Details' => 'Varchar(255)'
	];
}
