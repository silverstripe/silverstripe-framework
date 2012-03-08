<?php

class SS_MapTest extends SapphireTest {
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
	
	function testArrayAccess() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals('This is a team comment by Joe', $map['Joe']);
		$this->assertNull($map['DoesntExist']);
	}

	function testIteration() {
		$list = DataList::create("DataObjectTest_TeamComment")->sort('ID');
		$map = new SS_Map($list, 'Name', 'Comment');
		$text = "";
		foreach($map as $k => $v) {
			$text .= "$k: $v\n";
		}
		$this->assertEquals("Joe: This is a team comment by Joe\n"
			. "Bob: This is a team comment by Bob\n"
			. "Phil: Phil is a unique guy, and comments on team2\n", $text);
	}
	
	function testDefaultConfigIsIDAndTitle() {
		$list = DataList::create("DataObjectTest_Team");
		$map = new SS_Map($list);
		$this->assertEquals('Team 1', $map[$this->idFromFixture('DataObjectTest_Team', 'team1')]);
	}
	
	function testSetKeyFieldAndValueField() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$map = new SS_Map($list);
		$map->setKeyField('Name');
		$map->setValueField('Comment');
		$this->assertEquals('This is a team comment by Joe', $map['Joe']);
	}
	
	function testToArray() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals(array("Joe" => "This is a team comment by Joe",
			"Bob" => "This is a team comment by Bob",
			"Phil" => "Phil is a unique guy, and comments on team2"), $map->toArray());
	}

	function testKeys() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$list->sort('Name');
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals(array(
			'Bob',
			'Joe',
			'Phil'
		), $map->keys());
	}

	function testValues() {
		$list = DataList::create('DataObjectTest_TeamComment');
		$list->sort('Name');
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals(array(
			'This is a team comment by Bob',
			'This is a team comment by Joe',
			'Phil is a unique guy, and comments on team2'
		), $map->values());
	}

	function testUnshift() {
		$list = DataList::create("DataObjectTest_TeamComment");
		$map = new SS_Map($list, 'Name', 'Comment');

		$map->unshift(-1, '(All)');

		$this->assertEquals(array(
			-1 => "(All)",
			"Joe" => "This is a team comment by Joe",
			"Bob" => "This is a team comment by Bob",
			"Phil" => "Phil is a unique guy, and comments on team2"), $map->toArray());

		$map->unshift(0, '(Select)');
		
		$this->assertEquals('(All)', $map[-1]);
		$this->assertEquals('(Select)', $map[0]);
		
		$this->assertEquals(array(
			0 => "(Select)",
			-1 => "(All)",
			"Joe" => "This is a team comment by Joe",
			"Bob" => "This is a team comment by Bob",
			"Phil" => "Phil is a unique guy, and comments on team2"), $map->toArray());

		$map->unshift("Bob","Replaced");
		$this->assertEquals(array(
			"Bob" => "Replaced",
			0 => "(Select)",
			-1 => "(All)",
			"Joe" => "This is a team comment by Joe",
			"Phil" => "Phil is a unique guy, and comments on team2"), $map->toArray());

		$map->unshift("Phil","Replaced as well");
		$this->assertEquals(array(
			"Phil" => "Replaced as well",
			"Bob" => "Replaced",
			0 => "(Select)",
			-1 => "(All)",
			"Joe" => "This is a team comment by Joe"), $map->toArray());

		$map->unshift("Joe","Replaced the last one");
		$this->assertEquals(array(
			"Joe" => "Replaced the last one",
			"Phil" => "Replaced as well",
			"Bob" => "Replaced",
			0 => "(Select)",
			-1 => "(All)"), $map->toArray());

	}
	
}