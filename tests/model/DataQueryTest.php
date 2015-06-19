<?php

class DataQueryTest extends SapphireTest {

	protected static $fixture_file = 'DataQueryTest.yml';

	protected $extraDataObjects = array(
		'DataQueryTest_A',
		'DataQueryTest_B',
		'DataQueryTest_C',
		'DataQueryTest_D',
		'DataQueryTest_E',
		'DataQueryTest_F',
	);


	public function testSortByJoinedFieldRetainsSourceInformation() {
		$bar = new DataQueryTest_C();
		$bar->Title = "Bar";
		$bar->write();

		$foo = new DataQueryTest_B();
		$foo->Title = "Foo";
		$foo->TestC = $bar->ID;
		$foo->write();

		$query = new DataQuery('DataQueryTest_B');
		$result = $query->leftJoin(
			'DataQueryTest_C',
			"\"DataQueryTest_B\".\"TestCID\" = \"DataQueryTest_B\".\"ID\""
		)->sort('"DataQueryTest_B"."Title"', 'ASC');

		$result = $result->execute()->record();
		$this->assertEquals('Foo', $result['Title']);
	}

	/**
	 * Test the leftJoin() and innerJoin method of the DataQuery object
	 */
	public function testJoins() {
		$dq = new DataQuery('Member');
		$dq->innerJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertSQLContains("INNER JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
			$dq->sql($parameters));

		$dq = new DataQuery('Member');
		$dq->leftJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertSQLContains("LEFT JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
			$dq->sql($parameters));
	}

	public function testApplyRelation() {
		// Test applyRelation with two has_ones pointing to the same class
		$dq = new DataQuery('DataQueryTest_B');
		$dq->applyRelation('TestC');
		$this->assertTrue($dq->query()->isJoinedTo('DataQueryTest_C'));
		$this->assertContains('"DataQueryTest_C"."ID" = "DataQueryTest_B"."TestCID"', $dq->sql());

		$dq = new DataQuery('DataQueryTest_B');
		$dq->applyRelation('TestCTwo');
		$this->assertTrue($dq->query()->isJoinedTo('DataQueryTest_C'));
		$this->assertContains('"DataQueryTest_C"."ID" = "DataQueryTest_B"."TestCTwoID"', $dq->sql());
	}

	public function testApplyReplationDeepInheretence() {
		$newDQ = new DataQuery('DataQueryTest_E');
		//apply a relation to a relation from an ancestor class
		$newDQ->applyRelation('TestA');
		$this->assertTrue($newDQ->query()->isJoinedTo('DataQueryTest_C'));
		$this->assertContains('"DataQueryTest_A"."ID" = "DataQueryTest_C"."TestAID"', $newDQ->sql($params));
	}

	public function testRelationReturn() {
		$dq = new DataQuery('DataQueryTest_C');
		$this->assertEquals('DataQueryTest_A', $dq->applyRelation('TestA'),
			'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_A', $dq->applyRelation('TestAs'),
			'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_A', $dq->applyRelation('ManyTestAs'),
			'DataQuery::applyRelation should return the name of the related object.');

		$this->assertEquals('DataQueryTest_B', $dq->applyRelation('TestB'),
			'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_B', $dq->applyRelation('TestBs'),
			'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_B', $dq->applyRelation('ManyTestBs'),
			'DataQuery::applyRelation should return the name of the related object.');
		$newDQ = new DataQuery('DataQueryTest_E');
		$this->assertEquals('DataQueryTest_A', $newDQ->applyRelation('TestA'),
			'DataQuery::applyRelation should return the name of the related object.');
	}

	public function testRelationOrderWithCustomJoin() {
		$dataQuery = new DataQuery('DataQueryTest_B');
		$dataQuery->innerJoin('DataQueryTest_D', '"DataQueryTest_D"."RelationID" = "DataQueryTest_B"."ID"');
		$dataQuery->execute();
	}

	public function testDisjunctiveGroup() {
		$dq = new DataQuery('DataQueryTest_A');

		$dq->where('DataQueryTest_A.ID = 2');
		$subDq = $dq->disjunctiveGroup();
		$subDq->where('DataQueryTest_A.Name = \'John\'');
		$subDq->where('DataQueryTest_A.Name = \'Bob\'');

		$this->assertSQLContains(
			"WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') OR (DataQueryTest_A.Name = 'Bob'))",
			$dq->sql($parameters)
		);
	}

	public function testConjunctiveGroup() {
		$dq = new DataQuery('DataQueryTest_A');

		$dq->where('DataQueryTest_A.ID = 2');
		$subDq = $dq->conjunctiveGroup();
		$subDq->where('DataQueryTest_A.Name = \'John\'');
		$subDq->where('DataQueryTest_A.Name = \'Bob\'');

		$this->assertSQLContains(
			"WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') AND (DataQueryTest_A.Name = 'Bob'))",
			$dq->sql($parameters)
		);
	}

	/**
	 * @todo Test paramaterised
	 */
	public function testNestedGroups() {
		$dq = new DataQuery('DataQueryTest_A');

		$dq->where('DataQueryTest_A.ID = 2');
		$subDq = $dq->disjunctiveGroup();
		$subDq->where('DataQueryTest_A.Name = \'John\'');
		$subSubDq = $subDq->conjunctiveGroup();
		$subSubDq->where('DataQueryTest_A.Age = 18');
		$subSubDq->where('DataQueryTest_A.Age = 50');
		$subDq->where('DataQueryTest_A.Name = \'Bob\'');

		$this->assertSQLContains(
			"WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') OR ((DataQueryTest_A.Age = 18) "
				. "AND (DataQueryTest_A.Age = 50)) OR (DataQueryTest_A.Name = 'Bob'))",
			$dq->sql($parameters)
		);
	}

	public function testEmptySubgroup() {
		$dq = new DataQuery('DataQueryTest_A');
		$dq->conjunctiveGroup();

		// Empty groups should have no where condition at all
		$this->assertSQLNotContains('WHERE', $dq->sql($parameters));
	}

	public function testSubgroupHandoff() {
		$dq = new DataQuery('DataQueryTest_A');
		$subDq = $dq->disjunctiveGroup();

		$orgDq = clone $dq;

		$subDq->sort('"DataQueryTest_A"."Name"');
		$orgDq->sort('"DataQueryTest_A"."Name"');

		$this->assertSQLEquals($dq->sql($parameters), $orgDq->sql($parameters));

		$subDq->limit(5, 7);
		$orgDq->limit(5, 7);

		$this->assertSQLEquals($dq->sql($parameters), $orgDq->sql($parameters));
	}

	public function testOrderByMultiple() {
		$dq = new DataQuery('SQLQueryTest_DO');
		$dq = $dq->sort('"Name" ASC, MID("Name", 8, 1) DESC');
		$this->assertContains(
			'ORDER BY "SQLQueryTest_DO"."Name" ASC, "_SortColumn0" DESC',
			$dq->sql($parameters)
		);
	}

	public function testDefaultSort() {
		$query = new DataQuery('DataQueryTest_E');
		$result = $query->column('Title');
		$this->assertEquals(array('First', 'Second', 'Last'), $result);
	}

	public function testDistinct() {
		$query = new DataQuery('DataQueryTest_E');
		$this->assertContains('SELECT DISTINCT', $query->sql($params), 'Query is set as distinct by default');

		$query = $query->distinct(false);
		$this->assertNotContains('SELECT DISTINCT', $query->sql($params), 'Query does not contain distinct');

		$query = $query->distinct(true);
		$this->assertContains('SELECT DISTINCT', $query->sql($params), 'Query contains distinct');
 	}
	
	public function testComparisonClauseInt() {
		DB::query("INSERT INTO \"DataQueryTest_F\" (\"SortOrder\") VALUES (2)");
		$query = new DataQuery('DataQueryTest_F');
		$query->where(DB::get_conn()->comparisonClause('"SortOrder"', '2'));
		$this->assertGreaterThan(0, $query->count(), "Couldn't find SortOrder");
		$this->resetDBSchema(true);
	}
	
	public function testComparisonClauseDateFull() {
		DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyDate\") VALUES ('1988-03-04 06:30')");
		$query = new DataQuery('DataQueryTest_F');
		$query->where(DB::get_conn()->comparisonClause('"MyDate"', '1988-03-04%'));
		$this->assertGreaterThan(0, $query->count(), "Couldn't find MyDate");
		$this->resetDBSchema(true);
	}
	
	public function testComparisonClauseDateStartsWith() {
		DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyDate\") VALUES ('1988-03-04 06:30')");
		$query = new DataQuery('DataQueryTest_F');
		$query->where(DB::get_conn()->comparisonClause('"MyDate"', '1988%'));
		$this->assertGreaterThan(0, $query->count(), "Couldn't find MyDate");
		$this->resetDBSchema(true);
	}
	
	public function testComparisonClauseDateStartsPartial() {
		DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyDate\") VALUES ('1988-03-04 06:30')");
		$query = new DataQuery('DataQueryTest_F');
		$query->where(DB::get_conn()->comparisonClause('"MyDate"', '%03-04%'));
		$this->assertGreaterThan(0, $query->count(), "Couldn't find MyDate");
		$this->resetDBSchema(true);
	}
	
	public function testComparisonClauseTextCaseInsensitive() {
		DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyString\") VALUES ('HelloWorld')");
		$query = new DataQuery('DataQueryTest_F');
		$query->where(DB::get_conn()->comparisonClause('"MyString"', 'helloworld'));
		$this->assertGreaterThan(0, $query->count(), "Couldn't find MyString");
		$this->resetDBSchema(true);
	}
	
	public function testComparisonClauseTextCaseSensitive() {
		DB::query("INSERT INTO \"DataQueryTest_F\" (\"MyString\") VALUES ('HelloWorld')");
		$query = new DataQuery('DataQueryTest_F');
		$query->where(DB::get_conn()->comparisonClause('"MyString"', 'HelloWorld', false, false, true));
		$this->assertGreaterThan(0, $query->count(), "Couldn't find MyString");
		
		$query2 = new DataQuery('DataQueryTest_F');
		$query2->where(DB::get_conn()->comparisonClause('"MyString"', 'helloworld', false, false, true));
		$this->assertEquals(0, $query2->count(), "Found mystring. Shouldn't be able too.");
		$this->resetDBSchema(true);
	}

}


class DataQueryTest_A extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar',
	);

	private static $has_one = array(
		'TestC' => 'DataQueryTest_C',
	);
}

class DataQueryTest_B extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar',
	);

	private static $has_one = array(
		'TestC' => 'DataQueryTest_C',
		'TestCTwo' => 'DataQueryTest_C',
	);
}

class DataQueryTest_C extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);

	private static $has_one = array(
		'TestA' => 'DataQueryTest_A',
		'TestB' => 'DataQueryTest_B',
	);

	private static $has_many = array(
		'TestAs' => 'DataQueryTest_A',
		'TestBs' => 'DataQueryTest_B.TestC',
		'TestBsTwo' => 'DataQueryTest_B.TestCTwo',
	);

	private static $many_many = array(
		'ManyTestAs' => 'DataQueryTest_A',
		'ManyTestBs' => 'DataQueryTest_B',
	);
}

class DataQueryTest_D extends DataObject implements TestOnly {

	private static $has_one = array(
		'Relation' => 'DataQueryTest_B',
	);
}

class DataQueryTest_E extends DataQueryTest_C implements TestOnly {

	private static $db = array(
		'SortOrder' => 'Int'
	);

	private static $default_sort = '"DataQueryTest_E"."SortOrder" ASC';
}

class DataQueryTest_F extends DataObject implements TestOnly {

	private static $db = array(
		'SortOrder' => 'Int',
		'MyDate' => 'SS_Datetime',
		'MyString' => 'Text'
	);
}
