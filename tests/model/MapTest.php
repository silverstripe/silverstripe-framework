<?php

/**
 * @package framework
 * @subpackage tests
 */
class SS_MapTest extends SapphireTest {

	// Borrow the model from DataObjectTest
	protected static $fixture_file = 'DataObjectTest.yml';

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


	public function testValues() {
		$list = DataObjectTest_TeamComment::get()->sort('Name');
		$map = new SS_Map($list, 'Name', 'Comment');

		$this->assertEquals(array(
			'This is a team comment by Bob',
			'This is a team comment by Joe',
			'Phil is a unique guy, and comments on team2'
		), $map->values());


		$map->push('Push', 'Item');

		$this->assertEquals(array(
			'This is a team comment by Bob',
			'This is a team comment by Joe',
			'Phil is a unique guy, and comments on team2',
			'Item'
		), $map->values());

		$map = new SS_Map(new ArrayList());
		$map->push('Push', 'Pushed value');

		$this->assertEquals(array(
			'Pushed value'
		), $map->values());

		$map = new SS_Map(new ArrayList());
		$map->unshift('Unshift', 'Unshift item');

		$this->assertEquals(array(
			'Unshift item'
		), $map->values());
	}


	public function testArrayAccess() {
		$list = DataObjectTest_TeamComment::get();
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals('This is a team comment by Joe', $map['Joe']);
		$this->assertNull($map['DoesntExist']);
	}

	public function testIteration() {
		$list = DataObjectTest_TeamComment::get()->sort('ID');
		$map = new SS_Map($list, 'Name', 'Comment');
		$text = "";
		foreach($map as $k => $v) {
			$text .= "$k: $v\n";
		}
		$this->assertEquals("Joe: This is a team comment by Joe\n"
			. "Bob: This is a team comment by Bob\n"
			. "Phil: Phil is a unique guy, and comments on team2\n", $text);
	}

	public function testDefaultConfigIsIDAndTitle() {
		$list = DataObjectTest_Team::get();
		$map = new SS_Map($list);
		$this->assertEquals('Team 1', $map[$this->idFromFixture('DataObjectTest_Team', 'team1')]);
	}

	public function testSetKeyFieldAndValueField() {
		$list = DataObjectTest_TeamComment::get();
		$map = new SS_Map($list);
		$map->setKeyField('Name');
		$map->setValueField('Comment');
		$this->assertEquals('This is a team comment by Joe', $map['Joe']);
	}

	public function testToArray() {
		$list = DataObjectTest_TeamComment::get();
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals(array("Joe" => "This is a team comment by Joe",
			"Bob" => "This is a team comment by Bob",
			"Phil" => "Phil is a unique guy, and comments on team2"), $map->toArray());
	}

	public function testKeys() {
		$list = DataObjectTest_TeamComment::get()->sort('Name');
		$map = new SS_Map($list, 'Name', 'Comment');
		$this->assertEquals(array(
			'Bob',
			'Joe',
			'Phil'
		), $map->keys());

		$map->unshift('Unshift', 'Item');

		$this->assertEquals(array(
			'Unshift',
			'Bob',
			'Joe',
			'Phil'
		), $map->keys());

		$map->push('Push', 'Item');

		$this->assertEquals(array(
			'Unshift',
			'Bob',
			'Joe',
			'Phil',
			'Push'
		), $map->keys());

		$map = new SS_Map(new ArrayList());
		$map->push('Push', 'Item');

		$this->assertEquals(array(
			'Push'
		), $map->keys());

		$map = new SS_Map(new ArrayList());
		$map->unshift('Unshift', 'Item');

		$this->assertEquals(array(
			'Unshift'
		), $map->keys());
	}

	public function testMethodAsValueField() {
		$list = DataObjectTest_Team::get()->sort('Title');
		$map = new SS_Map($list, 'ID', 'MyTitle');
		$this->assertEquals(array(
			'Team Subteam 1',
			'Team Subteam 2',
			'Team Subteam 3',
			'Team Team 1',
			'Team Team 2',
			'Team Team 3'
		), $map->values());
	}

	public function testUnshift() {
		$list = DataObjectTest_TeamComment::get();
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

	public function testPush() {
		$list = DataObjectTest_TeamComment::get();
		$map = new SS_Map($list, 'Name', 'Comment');

		$map->push(1, '(All)');

		$this->assertEquals(array(
			"Joe" => "This is a team comment by Joe",
			"Bob" => "This is a team comment by Bob",
			"Phil" => "Phil is a unique guy, and comments on team2",
			1 => "(All)"
		), $map->toArray());
	}

	public function testCount() {
		$list = DataObjectTest_TeamComment::get();
		$map = new SS_Map($list, 'Name', 'Comment');

		$this->assertEquals(3, $map->count());

		// pushing a new item should update the count
		$map->push(1, 'Item pushed');
		$this->assertEquals(4, $map->count());

		$map->unshift(2, 'Item shifted');
		$this->assertEquals(5, $map->count());

		$map = new SS_Map(new ArrayList());
		$map->unshift('1', 'shifted');

		$this->assertEquals(1, $map->count());

		unset($map[1]);
		$this->assertEquals(0, $map->count());
	}

	public function testIterationWithUnshift() {
		$list = DataObjectTest_TeamComment::get()->sort('ID');
		$map = new SS_Map($list, 'Name', 'Comment');
		$map->unshift(1, 'Unshifted');

		$text = "";

		foreach($map as $k => $v) {
			$text .= "$k: $v\n";
		}

		$this->assertEquals("1: Unshifted\n"
			. "Joe: This is a team comment by Joe\n"
			. "Bob: This is a team comment by Bob\n"
			. "Phil: Phil is a unique guy, and comments on team2\n", $text
		);
	}

	public function testIterationWithPush() {
		$list = DataObjectTest_TeamComment::get()->sort('ID');
		$map = new SS_Map($list, 'Name', 'Comment');
		$map->push(1, 'Pushed');

		$text = "";

		foreach($map as $k => $v) {
			$text .= "$k: $v\n";
		}

		$this->assertEquals("Joe: This is a team comment by Joe\n"
			. "Bob: This is a team comment by Bob\n"
			. "Phil: Phil is a unique guy, and comments on team2\n"
			. "1: Pushed\n", $text
		);
	}

	public function testIterationWithEmptyListUnshifted() {
		$map = new SS_Map(new ArrayList());
		$map->unshift('1', 'unshifted');

		$text = "";

		foreach($map as $k => $v) {
			$text .= "$k: $v\n";
		}

		$this->assertEquals("1: unshifted\n", $text);
	}

	public function testIterationWithEmptyListPushed() {
		$map = new SS_Map(new ArrayList());
		$map->push('1', 'pushed');

		$text = "";

		foreach($map as $k => $v) {
			$text .= "$k: $v\n";
		}

		$this->assertEquals("1: pushed\n", $text);
	}
}
