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
	
	public function testSubtract(){
		$subtractList = DataList::create("DataObjectTest_TeamComment")->filter('ID',1);
		$fullList = DataList::create("DataObjectTest_TeamComment");
		$newList = $fullList->subtract($subtractList);
		$this->assertEquals(2, $newList->Count(), 'List should only contain two objects after subtraction');
	}
	
	public function testSubtractBadDataclassThrowsException(){
		$this->setExpectedException('InvalidArgumentException');
		$teamsComments = DataList::create("DataObjectTest_TeamComment");
		$teams = DataList::create("DataObjectTest_Team");
		$teamsComments->subtract($teams);
	}
	
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
		$this->assertEquals(array('Joe', 'Phil'), $list->limit(2, 1)->column('Name'));
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
				'TeamID'=> $this->objFromFixture('DataObjectTest_TeamComment', 'comment1')->TeamID,
			),
			1=>
			array(
				'ClassName'=>'DataObjectTest_TeamComment',
				'Name'=>'Bob',
				'Comment'=>'This is a team comment by Bob',
				'TeamID'=> $this->objFromFixture('DataObjectTest_TeamComment', 'comment2')->TeamID,
			),
			2=>
			array(
				'ClassName'=>'DataObjectTest_TeamComment',
				'Name'=>'Phil',
				'Comment'=>'Phil is a unique guy, and comments on team2',
				'TeamID'=> $this->objFromFixture('DataObjectTest_TeamComment', 'comment3')->TeamID,
			),
		);
		$this->assertEquals(3, count($nestedArray));
		$this->assertEquals($expected[0]['Name'], $nestedArray[0]['Name']);
		$this->assertEquals($expected[1]['Comment'], $nestedArray[1]['Comment']);
		$this->assertEquals($expected[2]['TeamID'], $nestedArray[2]['TeamID']);
	}
	
	function testMap() {
		$map = DataList::create('DataObjectTest_TeamComment')->map()->toArray();
		$expected = array(
			$this->idFromFixture('DataObjectTest_TeamComment', 'comment1') => 'Joe',
			$this->idFromFixture('DataObjectTest_TeamComment', 'comment2') => 'Bob',
			$this->idFromFixture('DataObjectTest_TeamComment', 'comment3') => 'Phil'
		);
	
		$this->assertEquals($expected, $map);
		$otherMap = DataList::create('DataObjectTest_TeamComment')->map('Name', 'TeamID')->toArray();
		$otherExpected = array(
			'Joe' => $this->objFromFixture('DataObjectTest_TeamComment', 'comment1')->TeamID,
			'Bob' => $this->objFromFixture('DataObjectTest_TeamComment', 'comment2')->TeamID,
			'Phil' => $this->objFromFixture('DataObjectTest_TeamComment', 'comment3')->TeamID
		);
	
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
		$this->assertType('DataObjectTest_Team', $team);
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
	
	public function testSimpleSort() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('Name');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortOneArgumentASC() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('Name ASC');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortOneArgumentDESC() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('Name DESC');
		$this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
		$this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
	}
	
	public function testSortOneArgumentMultipleColumns() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('TeamID ASC, Name DESC');
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortASC() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('Name', 'asc');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortDESC() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('Name', 'desc');
		$this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
		$this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
	}
	
	public function testSortWithArraySyntaxSortASC() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort(array('Name'=>'asc'));
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSortWithArraySyntaxSortDESC() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort(array('Name'=>'desc'));
		$this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
		$this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
	}
	
	public function testSortWithMultipleArraySyntaxSort() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort(array('TeamID'=>'asc','Name'=>'desc'));
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	/**
	 * $list->filter('Name', 'bob'); // only bob in the list
	 */
	public function testSimpleFilter() {
		$list = DataList::create("DataObjectTest_Team");
		$list->filter('Title','Team 2');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Team 2', $list->first()->Title, 'List should only contain Team 2');
		$this->assertEquals('Team 2', $list->last()->Title, 'Last should only contain Team 2');
	}
	
	public function testSimpleFilterEndsWith() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('Name:EndsWith', 'b');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimpleFilterExactMatchFilter() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('Name:ExactMatch', 'Bob');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimpleFilterGreaterThanFilter() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('TeamID:GreaterThan', $this->idFromFixture('DataObjectTest_Team', 'team1'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Bob');
	}

	// public function testSimpleFilterLessThanFilter() {
	// 	$list = DataList::create("DataObjectTest_TeamComment");
	// 	$list = $list->filter('TeamID:LessThan', $this->idFromFixture('DataObjectTest_TeamComment', 'comment2'))->sort('Name');
	// 	$this->assertEquals(2, $list->count());
	// 	$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	// 	$this->assertEquals('Joe', $list->Last()->Name, 'Last comment should be from Joe');
	// }

	public function testSimpleNegationFilter() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('TeamID:Negation', $this->idFromFixture('DataObjectTest_Team', 'team1'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimplePartialMatchFilter() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('Name:PartialMatch', 'o')->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Joe', $list->last()->Name, 'First comment should be from Joe');
	}

	public function testSimpleFilterStartsWith() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('Name:StartsWith', 'B');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimpleFilterWithNonExistingComparisator() {
		$this->setExpectedException('InvalidArgumentException');
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('Comment:Bogus', 'team comment');
	}

	/**
	 * $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 */
	public function testSimpleFilterWithMultiple() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter('Name', array('Bob','Phil'));
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testMultipleFilterWithNoMatch() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter(array('Name'=>'Bob', 'Comment'=>'Phil is a unique guy, and comments on team2'));
		$this->assertEquals(0, $list->count());
	}
	
	/**
	 *  $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
	 */
	public function testFilterMultipleArray() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
	}
	
	public function testFilterMultipleWithTwoMatches() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter(array('TeamID'=>$this->idFromFixture('DataObjectTest_Team', 'team1')));
		$this->assertEquals(2, $list->count());
	}
	
	public function testFilterMultipleWithArrayFilter() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter(array('Name'=>array('Bob','Phil')));
		$this->assertEquals(2, $list->count(), 'There should be two comments');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	/**
	 * $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
	 */
	public function testFilterArrayInArray() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->filter(array('Name'=>array('Bob','Phil'), 'TeamID'=>array($this->idFromFixture('DataObjectTest_Team', 'team1'))));
		$this->assertEquals(1, $list->count(), 'There should be one comments');
		$this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
	}
	
	/**
	 * $list->exclude('Name', 'bob'); // exclude bob from list
	 */
	public function testSimpleExclude() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude('Name', 'Bob');
		$list->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
//	
	/**
	 * $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 */
	public function testSimpleExcludeWithMultiple() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude('Name', array('Joe','Phil'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // negative version
	 */
	public function testMultipleExcludeWithMiss() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name'=>'Bob', 'Comment'=>'Does not match any comments'));
		$this->assertEquals(3, $list->count());
	}
	
	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 */
	public function testMultipleExclude() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
		$this->assertEquals(2, $list->count());
	}
	
	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 */
	public function testMultipleExcludeWithMultipleThatCheersEitherTeam() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name'=>'Bob', 'TeamID'=>array(
			$this->idFromFixture('DataObjectTest_Team', 'team1'),
			$this->idFromFixture('DataObjectTest_Team', 'team2')
		)));
		$list->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Phil');
		$this->assertEquals('Phil', $list->last()->Name, 'First comment should be from Phil');
	}
	
	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // negative version
	 */
	public function testMultipleExcludeWithMultipleThatCheersOnNonExistingTeam() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name'=>'Bob', 'TeamID'=>array(3)));
		$this->assertEquals(3, $list->count());
	}
	
	/**
	 * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); //negative version
	 */
	public function testMultipleExcludeWithNoExclusion() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name'=>array('Bob','Joe'), 'Comment' => 'Phil is a unique guy, and comments on team2'));
		$this->assertEquals(3, $list->count());
	}
	
	/**
	 *  $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); 
	 */
	public function testMultipleExcludeWithTwoArray() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name' => array('Bob','Joe'), 'TeamID' => array(
			$this->idFromFixture('DataObjectTest_Team', 'team1'),
			$this->idFromFixture('DataObjectTest_Team', 'team2')
		)));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Phil', $list->last()->Name, 'Only comment should be from Phil');
	}
	
	/**
	 *  $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); 
	 */
	public function testMultipleExcludeWithTwoArrayOneTeam() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->exclude(array('Name' => array('Bob', 'Phil'), 'TeamID' => array($this->idFromFixture('DataObjectTest_Team', 'team1'))));
		$list->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}

	/**
	 * 
	 */
	public function testSortByRelation() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list = $list->sort(array('Team.Title' => 'DESC'));
		$this->assertEquals(3, $list->count());
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team2'), $list->first()->TeamID, 'First comment should be for Team 2');
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $list->last()->TeamID, 'Last comment should be for Team 1');
	}
	
	public function testReverse() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$list->sort('Name');
		$list->reverse();
		
		$this->assertEquals('Bob', $list->last()->Name, 'Last comment should be from Bob');
		$this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Phil');
	}
}
