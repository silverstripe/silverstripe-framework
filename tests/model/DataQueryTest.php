<?php

class DataQueryTest extends SapphireTest {
	
	protected static $fixture_file = 'DataQueryTest.yml';

	protected $extraDataObjects = array(
		'DataQueryTest_A',
		'DataQueryTest_B',
		'DataQueryTest_C',
		'DataQueryTest_D',
		'DataQueryTest_E',
	);

	/**
	 * Test the leftJoin() and innerJoin method of the DataQuery object
	 */
	public function testJoins() {
		$dq = new DataQuery('Member');
		$dq->innerJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertContains("INNER JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
			$dq->sql());

		$dq = new DataQuery('Member');
		$dq->leftJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertContains("LEFT JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"",
			$dq->sql());
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

		$this->assertContains(
			"WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') OR (DataQueryTest_A.Name = 'Bob'))", 
			$dq->sql()
		);
	}

	public function testConjunctiveGroup() {
		$dq = new DataQuery('DataQueryTest_A');

		$dq->where('DataQueryTest_A.ID = 2');
		$subDq = $dq->conjunctiveGroup();
		$subDq->where('DataQueryTest_A.Name = \'John\'');
		$subDq->where('DataQueryTest_A.Name = \'Bob\'');

		$this->assertContains(
			"WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') AND (DataQueryTest_A.Name = 'Bob'))", 
			$dq->sql()
		);
	}

	public function testNestedGroups() {
		$dq = new DataQuery('DataQueryTest_A');

		$dq->where('DataQueryTest_A.ID = 2');
		$subDq = $dq->disjunctiveGroup();
		$subDq->where('DataQueryTest_A.Name = \'John\'');
		$subSubDq = $subDq->conjunctiveGroup();
		$subSubDq->where('DataQueryTest_A.Age = 18');
		$subSubDq->where('DataQueryTest_A.Age = 50');
		$subDq->where('DataQueryTest_A.Name = \'Bob\'');

		$this->assertContains(
			"WHERE (DataQueryTest_A.ID = 2) AND ((DataQueryTest_A.Name = 'John') OR ((DataQueryTest_A.Age = 18) "
				. "AND (DataQueryTest_A.Age = 50)) OR (DataQueryTest_A.Name = 'Bob'))", 
			$dq->sql()
		);
	}

	public function testEmptySubgroup() {
		$dq = new DataQuery('DataQueryTest_A');
		$dq->conjunctiveGroup();

		$this->assertContains('WHERE (1=1)', $dq->sql());
	}

	public function testSubgroupHandoff() {
		$dq = new DataQuery('DataQueryTest_A');
		$subDq = $dq->disjunctiveGroup();

		$orgDq = clone $dq;

		$subDq->sort('"DataQueryTest_A"."Name"');
		$orgDq->sort('"DataQueryTest_A"."Name"');

		$this->assertEquals($dq->sql(), $orgDq->sql());

		$subDq->limit(5, 7);
		$orgDq->limit(5, 7);

		$this->assertEquals($dq->sql(), $orgDq->sql());
	}
	
	public function testOrderByMultiple() {
		$dq = new DataQuery('SQLQueryTest_DO');
		$dq = $dq->sort('"Name" ASC, MID("Name", 8, 1) DESC');
		$this->assertContains(
			'ORDER BY "Name" ASC, "_SortColumn0" DESC',
			$dq->sql()
		);
	}
	
	public function testDefaultSort() {
		$query = new DataQuery('DataQueryTest_E');
		$result = $query->column('Title');
		$this->assertEquals(array('First', 'Second', 'Last'), $result);
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

class DataQueryTest_B extends DataQueryTest_A {
	private static $db = array(
		'Title' => 'Varchar',
	);

	private static $has_one = array(
		'TestC' => 'DataQueryTest_C',
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
		'TestBs' => 'DataQueryTest_B',
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
