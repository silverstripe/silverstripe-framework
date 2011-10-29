<?php

class DataListTest extends SapphireTest {
	// Borrow the model from DataObjectTest
	static $fixture_file = 'DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_Fixture',
		'DataObjectTest_SubTeam',
		'OtherSubclassWithSameField',
		'DataObjectTest_FieldlessTable',
		'DataObjectTest_FieldlessSubTable',
		'DataObjectTest_ValidatedObject',
		'DataObjectTest_Player',
		'DataObjectTest_TeamComment'
	);

	function testListCreationSortAndLimit() {
		// By default, a DataList will contain all items of that class
		$list = DataList::create('DataObjectTest_TeamComment')->sort('ID');
		
		// We can iterate on the DataList
		$names = array();
		foreach($list as $item) {
			$names[] = $item->Name;
		}
		$this->assertEquals(array('Joe', 'Bob', 'Phil'), $names);

		// If we don't want to iterate, we can extract a single column from the list with column()
		$this->assertEquals(array('Joe', 'Bob', 'Phil'), $list->column('Name'));
		
		// We can sort a list
		$list = $list->sort('Name');
		$this->assertEquals(array('Bob', 'Joe', 'Phil'), $list->column('Name'));
		
		// We can also restrict the output to a range
		$this->assertEquals(array('Joe', 'Phil'), $list->getRange(1,2)->column('Name'));
	}
	
	function testDataClass() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$this->assertEquals('DataObjectTest_TeamComment',$list->dataClass());
	}
	
	function testClone() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$this->assertEquals($list, clone($list));
	}
	
	function testSql() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", "DataObjectTest_TeamComment"."Created", "DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", "DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL THEN "DataObjectTest_TeamComment"."ClassName" ELSE \'DataObjectTest_TeamComment\' END AS "RecordClassName" FROM "DataObjectTest_TeamComment"';
		$this->assertEquals($expected, $list->sql());
	}
	
	function testInnerJoin() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$list->innerJoin('DataObjectTest_Team', '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"', 'Team');
		$expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", "DataObjectTest_TeamComment"."Created", "DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", "DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL THEN "DataObjectTest_TeamComment"."ClassName" ELSE \'DataObjectTest_TeamComment\' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" INNER JOIN "DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"';
		$this->assertEquals($expected, $list->sql());
	}
	
	function testLeftJoin() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$list->leftJoin('DataObjectTest_Team', '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"', 'Team');
		$expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", "DataObjectTest_TeamComment"."Created", "DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", "DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL THEN "DataObjectTest_TeamComment"."ClassName" ELSE \'DataObjectTest_TeamComment\' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" LEFT JOIN "DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"';
		$this->assertEquals($expected, $list->sql());
	}
	
	function testToNestedArray() {
		$list = DataList::create('DataObjectTest_TeamComment')->sort('ID');
		$nestedArray = $list->toNestedArray();
		$expected = array(
			0=>
			array(
				'ClassName'=>'DataObjectTest_TeamComment',
				'Name'=>'Joe',
				'Comment'=>'This is a team comment by Joe',
				'TeamID'=>'1',
			),
			1=>
			array(
				'ClassName'=>'DataObjectTest_TeamComment',
				'Name'=>'Bob',
				'Comment'=>'This is a team comment by Bob',
				'TeamID'=>'1',
			),
			2=>
			array(
				'ClassName'=>'DataObjectTest_TeamComment',
				'Name'=>'Phil',
				'Comment'=>'Phil is a unique guy, and comments on team2',
				'TeamID'=>'2',
			),
		);
		$this->assertEquals(3, count($nestedArray));
		$this->assertEquals($expected[0]['Name'], $nestedArray[0]['Name']);
		$this->assertEquals($expected[1]['Comment'], $nestedArray[1]['Comment']);
		$this->assertEquals($expected[2]['TeamID'], $nestedArray[2]['TeamID']);
	}
	
	function testMap() {
		$map = DataList::create('DataObjectTest_TeamComment')->map()->toArray();
		$expected = array(1=>'Joe', 2=>'Bob', 3=>'Phil');
		$this->assertEquals($expected, $map);
		$otherMap = DataList::create('DataObjectTest_TeamComment')->map('Name', 'TeamID')->toArray();
		$otherExpected = array ('Joe' => '1','Bob' => '1','Phil' => '2');
		$this->assertEquals($otherExpected, $otherMap);
	}

	function testFilter() {
		// coming soon!
		}
		
	function testWhere() {
		// We can use raw SQL queries with where.  This is only recommended for advanced uses;
		// if you can, you should use filter().
		$list = DataList::create('DataObjectTest_TeamComment');
		
		// where() returns a new DataList, like all the other modifiers, so it can be chained.
		$list2 = $list->where('"Name" = \'Joe\'');
		$this->assertEquals(array('This is a team comment by Joe'), $list2->column('Comment'));
		
		// The where() clauses are chained together with AND
		$list3 = $list2->where('"Name" = \'Bob\'');
		$this->assertEquals(array(), $list3->column('Comment'));
	}
	
	/**
	 * Test DataList->byID()
	 */
	function testByID() {
		// We can get a single item by ID.
	    $id = $this->idFromFixture('DataObjectTest_Team','team2');
		$team = DataList::create("DataObjectTest_Team")->byID($id);
		
		// byID() returns a DataObject, rather than a DataList
		$this->assertInstanceOf('DataObjectTest_Team', $team);
	    $this->assertEquals('Team 2', $team->Title);
	}

	/**
	 * Test DataList->removeByID()
	 */
	function testRemoveByID() {
		$list = DataList::create("DataObjectTest_Team");
	    $id = $this->idFromFixture('DataObjectTest_Team','team2');
		
	    $this->assertNotNull($list->byID($id));
		$list->removeByID($id);
	    $this->assertNull($list->byID($id));
	}

	/**
	 * Test DataList->canSortBy()
	 */
	function testCanSortBy() {
	    // Basic check
		$team = DataList::create("DataObjectTest_Team");
	    $this->assertTrue($team->canSortBy("Title"));
	    $this->assertFalse($team->canSortBy("SomethingElse"));

        // Subclasses
		$subteam = DataList::create("DataObjectTest_SubTeam");
	    $this->assertTrue($subteam->canSortBy("Title"));
	    $this->assertTrue($subteam->canSortBy("SubclassDatabaseField"));
	}
	
	function testDataListArrayAccess() {
	    $list = DataList::create("DataObjectTest_Team")->sort("Title");
	
		// We can use array access to refer to single items in the DataList, as if it were an array
	    $this->assertEquals("Subteam 1", $list[0]->Title);
	    $this->assertEquals("Subteam 3", $list[2]->Title);
	    $this->assertEquals("Team 2", $list[4]->Title);
	}
	
	function testFind() {
	    $list = DataList::create("DataObjectTest_Team");
		$record = $list->find('Title', 'Team 1');
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $record->ID);
	}
	
	function testFindById() {
	    $list = DataList::create("DataObjectTest_Team");
		$record = $list->find('ID', $this->idFromFixture('DataObjectTest_Team', 'team1'));
		$this->assertEquals('Team 1', $record->Title);
		// Test that you can call it twice on the same list
		$record = $list->find('ID', $this->idFromFixture('DataObjectTest_Team', 'team2'));
		$this->assertEquals('Team 2', $record->Title);
	}
}