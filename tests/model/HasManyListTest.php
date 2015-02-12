<?php

class HasManyListTest extends SapphireTest {

	// Borrow the model from DataObjectTest
	protected static $fixture_file = 'DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
		'DataObjectTest_TeamComment',
	);

	public function testRelationshipEmptyOnNewRecords() {
		// Relies on the fact that (unrelated) comments exist in the fixture file already
		$newTeam = new DataObjectTest_Team(); // has_many Comments
		$this->assertEquals(array(), $newTeam->Comments()->column('ID'));
	}

	/**
	 * Test that related objects can be removed from a relation
	 */
	public function testRemoveRelation() {

		// Check that expected teams exist
		$list = DataObjectTest_Team::get();
		$this->assertEquals(
			array('Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'),
			$list->sort('Title')->column('Title')
		);

		// Test that each team has the correct fans
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
		$this->assertEquals(array('Bob', 'Joe'), $team1->Comments()->sort('Name')->column('Name'));
		$this->assertEquals(array('Phil'), $team2->Comments()->sort('Name')->column('Name'));

		// Test that removing comments from unrelated team has no effect
		$team1comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$team2comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment3');
		$team1->Comments()->remove($team2comment);
		$team2->Comments()->remove($team1comment);
		$this->assertEquals(array('Bob', 'Joe'), $team1->Comments()->sort('Name')->column('Name'));
		$this->assertEquals(array('Phil'), $team2->Comments()->sort('Name')->column('Name'));
		$this->assertEquals($team1->ID, $team1comment->TeamID);
		$this->assertEquals($team2->ID, $team2comment->TeamID);

		// Test that removing items from the related team resets the has_one relations on the fan
		$team1comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$team2comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment3');
		$team1->Comments()->remove($team1comment);
		$team2->Comments()->remove($team2comment);
		$this->assertEquals(array('Bob'), $team1->Comments()->sort('Name')->column('Name'));
		$this->assertEquals(array(), $team2->Comments()->sort('Name')->column('Name'));
		$this->assertEmpty($team1comment->TeamID);
		$this->assertEmpty($team2comment->TeamID);
	}

}
