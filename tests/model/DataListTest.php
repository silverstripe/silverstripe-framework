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
		'DataObjectTest_TeamComment',
		'DataObjectTest\NamespacedClass',
	);
	
	public function testSubtract(){
		$comment1 = $this->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$subtractList = DataObjectTest_TeamComment::get()->filter('ID', $comment1->ID);
		$fullList = DataObjectTest_TeamComment::get();
		$newList = $fullList->subtract($subtractList);
		$this->assertEquals(2, $newList->Count(), 'List should only contain two objects after subtraction');
	}
	
	public function testSubtractBadDataclassThrowsException(){
		$this->setExpectedException('InvalidArgumentException');
		$teamsComments = DataObjectTest_TeamComment::get();
		$teams = DataObjectTest_Team::get();
		$teamsComments->subtract($teams);
	}
	
	function testListCreationSortAndLimit() {
		// By default, a DataList will contain all items of that class
		$list = DataObjectTest_TeamComment::get()->sort('ID');
		
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
		$list = DataObjectTest_TeamComment::get();
		$this->assertEquals('DataObjectTest_TeamComment',$list->dataClass());
	}
	
	function testClone() {
		$list = DataObjectTest_TeamComment::get();
		$this->assertEquals($list, clone($list));
	}
	
	function testSql() {
		$db = DB::getConn();
		$list = DataObjectTest_TeamComment::get();
		$expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", "DataObjectTest_TeamComment"."Created", "DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", "DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL THEN "DataObjectTest_TeamComment"."ClassName" ELSE '.$db->prepStringForDB('DataObjectTest_TeamComment').' END AS "RecordClassName" FROM "DataObjectTest_TeamComment"';
		$this->assertEquals($expected, $list->sql());
	}
	
	function testInnerJoin() {
		$db = DB::getConn();
		$list = DataObjectTest_TeamComment::get();
		$list->innerJoin('DataObjectTest_Team', '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"', 'Team');
		$expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", "DataObjectTest_TeamComment"."Created", "DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", "DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL THEN "DataObjectTest_TeamComment"."ClassName" ELSE '.$db->prepStringForDB('DataObjectTest_TeamComment').' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" INNER JOIN "DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"';
		$this->assertEquals($expected, $list->sql());
	}
	
	function testLeftJoin() {
		$db = DB::getConn();
		$list = DataObjectTest_TeamComment::get();
		$list->leftJoin('DataObjectTest_Team', '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"', 'Team');
		$expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", "DataObjectTest_TeamComment"."Created", "DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", "DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL THEN "DataObjectTest_TeamComment"."ClassName" ELSE '.$db->prepStringForDB('DataObjectTest_TeamComment').' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" LEFT JOIN "DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"';
		$this->assertEquals($expected, $list->sql());
	}
	
	function testToNestedArray() {
		$list = DataObjectTest_TeamComment::get()->sort('ID');
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
		$map = DataObjectTest_TeamComment::get()->map()->toArray();
		$expected = array(
			$this->idFromFixture('DataObjectTest_TeamComment', 'comment1') => 'Joe',
			$this->idFromFixture('DataObjectTest_TeamComment', 'comment2') => 'Bob',
			$this->idFromFixture('DataObjectTest_TeamComment', 'comment3') => 'Phil'
		);
	
		$this->assertEquals($expected, $map);
		$otherMap = DataObjectTest_TeamComment::get()->map('Name', 'TeamID')->toArray();
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
		$list = DataObjectTest_TeamComment::get();
		
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
		$team = DataObjectTest_Team::get()->byID($id);
		
		// byID() returns a DataObject, rather than a DataList
		$this->assertInstanceOf('DataObjectTest_Team', $team);
		$this->assertEquals('Team 2', $team->Title);
	}
	
	/**
	 * Test DataList->removeByID()
	 */
	function testRemoveByID() {
		$list = DataObjectTest_Team::get();
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
		$team = DataObjectTest_Team::get();
		$this->assertTrue($team->canSortBy("Title"));
		$this->assertFalse($team->canSortBy("SomethingElse"));
	
		// Subclasses
		$subteam = DataObjectTest_SubTeam::get();
		$this->assertTrue($subteam->canSortBy("Title"));
		$this->assertTrue($subteam->canSortBy("SubclassDatabaseField"));
	}
	
	function testDataListArrayAccess() {
		$list = DataObjectTest_Team::get()->sort('Title');
	
		// We can use array access to refer to single items in the DataList, as if it were an array
		$this->assertEquals("Subteam 1", $list[0]->Title);
		$this->assertEquals("Subteam 3", $list[2]->Title);
		$this->assertEquals("Team 2", $list[4]->Title);
	}
	
	function testFind() {
		$list = DataObjectTest_Team::get();
		$record = $list->find('Title', 'Team 1');
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $record->ID);
	}
	
	function testFindById() {
		$list = DataObjectTest_Team::get();
		$record = $list->find('ID', $this->idFromFixture('DataObjectTest_Team', 'team1'));
		$this->assertEquals('Team 1', $record->Title);
		// Test that you can call it twice on the same list
		$record = $list->find('ID', $this->idFromFixture('DataObjectTest_Team', 'team2'));
		$this->assertEquals('Team 2', $record->Title);
	}
	
	public function testSimpleSort() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('Name');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortOneArgumentASC() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('Name ASC');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortOneArgumentDESC() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('Name DESC');
		$this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
		$this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
	}
	
	public function testSortOneArgumentMultipleColumns() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('TeamID ASC, Name DESC');
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortASC() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('Name', 'asc');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSimpleSortDESC() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('Name', 'desc');
		$this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
		$this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
	}
	
	public function testSortWithArraySyntaxSortASC() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort(array('Name'=>'asc'));
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testSortWithArraySyntaxSortDESC() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort(array('Name'=>'desc'));
		$this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
		$this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
	}
	
	public function testSortWithMultipleArraySyntaxSort() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort(array('TeamID'=>'asc','Name'=>'desc'));
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	/**
	 * $list->filter('Name', 'bob'); // only bob in the list
	 */
	public function testSimpleFilter() {
		$list = DataObjectTest_Team::get();
		$list = $list->filter('Title','Team 2');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Team 2', $list->first()->Title, 'List should only contain Team 2');
		$this->assertEquals('Team 2', $list->last()->Title, 'Last should only contain Team 2');
	}
	
	public function testSimpleFilterEndsWith() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('Name:EndsWith', 'b');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimpleFilterExactMatchFilter() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('Name:ExactMatch', 'Bob');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimpleFilterGreaterThanFilter() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('TeamID:GreaterThan', $this->idFromFixture('DataObjectTest_Team', 'team1'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Bob');
	}

	// public function testSimpleFilterLessThanFilter() {
	// 	$list = DataObjectTest_TeamComment::get();
	// 	$list = $list->filter('TeamID:LessThan', $this->idFromFixture('DataObjectTest_TeamComment', 'comment2'))->sort('Name');
	// 	$this->assertEquals(2, $list->count());
	// 	$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	// 	$this->assertEquals('Joe', $list->Last()->Name, 'Last comment should be from Joe');
	// }

	public function testSimpleNegationFilter() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('TeamID:Negation', $this->idFromFixture('DataObjectTest_Team', 'team1'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimplePartialMatchFilter() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('Name:PartialMatch', 'o')->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Joe', $list->last()->Name, 'First comment should be from Joe');
	}

	public function testSimpleFilterStartsWith() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('Name:StartsWith', 'B');
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	public function testSimpleFilterWithNonExistingComparisator() {
		$this->setExpectedException('InvalidArgumentException');
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('Comment:Bogus', 'team comment');
	}

	/**
	 * $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 */
	public function testSimpleFilterWithMultiple() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter('Name', array('Bob','Phil'));
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	public function testMultipleFilterWithNoMatch() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter(array('Name'=>'Bob', 'Comment'=>'Phil is a unique guy, and comments on team2'));
		$this->assertEquals(0, $list->count());
	}
	
	/**
	 *  $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
	 */
	public function testFilterMultipleArray() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
	}
	
	public function testFilterMultipleWithTwoMatches() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter(array('TeamID'=>$this->idFromFixture('DataObjectTest_Team', 'team1')));
		$this->assertEquals(2, $list->count());
	}
	
	public function testFilterMultipleWithArrayFilter() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter(array('Name'=>array('Bob','Phil')));
		$this->assertEquals(2, $list->count(), 'There should be two comments');
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
	
	/**
	 * $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
	 */
	public function testFilterArrayInArray() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->filter(array('Name'=>array('Bob','Phil'), 'TeamID'=>array($this->idFromFixture('DataObjectTest_Team', 'team1'))));
		$this->assertEquals(1, $list->count(), 'There should be one comments');
		$this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
	}

	public function testFilterAndExcludeById() {
		$id = $this->idFromFixture('DataObjectTest_SubTeam', 'subteam1');
		$list = DataObjectTest_SubTeam::get()->filter('ID', $id);
		$this->assertEquals($id, $list->first()->ID);

		$list = DataObjectTest_SubTeam::get();
		$this->assertEquals(3, count($list));
		$this->assertEquals(2, count($list->exclude('ID', $id)));

		// Check that classes with namespaces work.
		$obj = new DataObjectTest\NamespacedClass();
		$obj->Name = "Test";
		$obj->write();

		$list = DataObjectTest\NamespacedClass::get()->filter('ID', $obj->ID);
		$this->assertEquals('Test', $list->First()->Name);
		$this->assertEquals(0, $list->exclude('ID', $obj->ID)->count());
	}

	/**
	 * $list->exclude('Name', 'bob'); // exclude bob from list
	 */
	public function testSimpleExclude() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude('Name', 'Bob');
		$list = $list->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}
//	
	/**
	 * $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 */
	public function testSimpleExcludeWithMultiple() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude('Name', array('Joe','Phil'));
		$this->assertEquals(1, $list->count());
		$this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
	}

	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // negative version
	 */
	public function testMultipleExcludeWithMiss() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude(array('Name'=>'Bob', 'Comment'=>'Does not match any comments'));
		$this->assertEquals(3, $list->count());
	}
	
	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 */
	public function testMultipleExclude() {
		$list = DataObjectTest_TeamComment::get();
		$list->exclude(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
		$this->assertEquals(2, $list->count());
	}

	/**
	 * Test that if an exclude() is applied to a filter(), the filter() is still preserved.
	 */
	public function testExcludeOnFilter() {
		$list = DataObjectTest_TeamComment::get();
 		$list = $list->filter('Comment', 'Phil is a unique guy, and comments on team2');
		$list = $list->exclude('Name', 'Bob');
		
		$this->assertContains('WHERE ("Comment" = \'Phil is a unique guy, and comments on team2\') AND ("Name" != \'Bob\')', $list->sql());
	}
	
	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 */
	public function testMultipleExcludeWithMultipleThatCheersEitherTeam() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude(array('Name'=>'Bob', 'TeamID'=>array(
			$this->idFromFixture('DataObjectTest_Team', 'team1'),
			$this->idFromFixture('DataObjectTest_Team', 'team2')
		)));
		$list = $list->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Phil');
		$this->assertEquals('Phil', $list->last()->Name, 'First comment should be from Phil');
	}
	
	/**
	 * $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // negative version
	 */
	public function testMultipleExcludeWithMultipleThatCheersOnNonExistingTeam() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude(array('Name'=>'Bob', 'TeamID'=>array(3)));
		$this->assertEquals(3, $list->count());
	}
	
	/**
	 * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); //negative version
	 */
	public function testMultipleExcludeWithNoExclusion() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude(array('Name'=>array('Bob','Joe'), 'Comment' => 'Phil is a unique guy, and comments on team2'));
		$this->assertEquals(3, $list->count());
	}
	
	/**
	 *  $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); 
	 */
	public function testMultipleExcludeWithTwoArray() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude(array('Name' => array('Bob','Joe'), 'TeamID' => array(
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
		$list = DataObjectTest_TeamComment::get();
		$list = $list->exclude(array('Name' => array('Bob', 'Phil'), 'TeamID' => array($this->idFromFixture('DataObjectTest_Team', 'team1'))));
		$list = $list->sort('Name');
		$this->assertEquals(2, $list->count());
		$this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
		$this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
	}

	/**
	 * 
	 */
	public function testSortByRelation() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort(array('Team.Title' => 'DESC'));
		$this->assertEquals(3, $list->count());
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team2'), $list->first()->TeamID, 'First comment should be for Team 2');
		$this->assertEquals($this->idFromFixture('DataObjectTest_Team', 'team1'), $list->last()->TeamID, 'Last comment should be for Team 1');
	}
	
	public function testReverse() {
		$list = DataObjectTest_TeamComment::get();
		$list = $list->sort('Name');
		$list = $list->reverse();
		
		$this->assertEquals('Bob', $list->last()->Name, 'Last comment should be from Bob');
		$this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Phil');
	}

	public function testSortByComplexExpression() {
		// Test an expression with both spaces and commas
		// This test also tests that column() can be called with a complex sort expression, so keep using column() below
		$list = DataObjectTest_Team::get()->sort('CASE WHEN "DataObjectTest_Team"."ClassName" = \'DataObjectTest_SubTeam\' THEN 0 ELSE 1 END, "Title" DESC');
		$this->assertEquals(array(
			'Subteam 3',
			'Subteam 2',
			'Subteam 1',
			'Team 3',
			'Team 2',
			'Team 1',
		), $list->column("Title"));
	}
}
