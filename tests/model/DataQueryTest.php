<?php

class DataQueryTest extends SapphireTest {
	/**
	 * Test the leftJoin() and innerJoin method of the DataQuery object
	 */
	function testJoins() {
		$dq = new DataQuery('Member');
		$dq->innerJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertContains("INNER JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"", $dq->sql());

		$dq = new DataQuery('Member');
		$dq->leftJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertContains("LEFT JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"", $dq->sql());
	}

	function testRelationReturn() {
		$dq = new DataQuery('DataQueryTest_C');
		$this->assertEquals('DataQueryTest_A', $dq->applyRelation('TestA'), 'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_A', $dq->applyRelation('TestAs'), 'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_A', $dq->applyRelation('ManyTestAs'), 'DataQuery::applyRelation should return the name of the related object.');

		$this->assertEquals('DataQueryTest_B', $dq->applyRelation('TestB'), 'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_B', $dq->applyRelation('TestBs'), 'DataQuery::applyRelation should return the name of the related object.');
		$this->assertEquals('DataQueryTest_B', $dq->applyRelation('ManyTestBs'), 'DataQuery::applyRelation should return the name of the related object.');
	}
}


class DataQueryTest_A extends DataObject implements TestOnly {
	public static $db = array(
		'Name' => 'Varchar',
	);

	public static $has_one = array(
		'TestC' => 'DataQueryTest_C',
	);
}

class DataQueryTest_B extends DataQueryTest_A {
	public static $db = array(
		'Title' => 'Varchar',
	);

	public static $has_one = array(
		'TestC' => 'DataQueryTest_C',
	);
}

class DataQueryTest_C extends DataObject implements TestOnly {
	public static $has_one = array(
		'TestA' => 'DataQueryTest_A',
		'TestB' => 'DataQueryTest_B',
	);

	public static $has_many = array(
		'TestAs' => 'DataQueryTest_A',
		'TestBs' => 'DataQueryTest_B',
	);

	public static $many_many = array(
		'ManyTestAs' => 'DataQueryTest_A',
		'ManyTestBs' => 'DataQueryTest_B',
	);
}
