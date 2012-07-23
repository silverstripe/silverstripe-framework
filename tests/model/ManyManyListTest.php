<?php

class ManyManyListTest extends SapphireTest {
	
	// Borrow the model from DataObjectTest
	public static $fixture_file = 'DataObjectTest.yml';

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
	);
	
	public function testCreateList() {
		$list = ManyManyList::create('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID', 'DataObjectTest_PlayerID');
		$this->assertEquals(2, $list->count());
	}

	public function testRelationshipEmptyOnNewRecords() {
		// Relies on the fact that (unrelated) teams exist in the fixture file already
		$newPlayer = new DataObjectTest_Player(); // many_many Teams
		$this->assertEquals(array(), $newPlayer->Teams()->column('ID'));
	}
	
	public function testAddingSingleDataObjectByReference() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->add($team1);
		$player1->flushCache();

		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID', 'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'),$compareTeams->column('ID'),"Adding single record as DataObject to many_many");
	}

	public function testRemovingSingleDataObjectByReference() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->remove($team1);
		$player1->flushCache();
		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID', 'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'),$compareTeams->column('ID'),"Removing single record as DataObject from many_many");
	}
	
	public function testAddingSingleDataObjectByID() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->add($team1->ID);
		$player1->flushCache();
		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID', 'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'), $compareTeams->column('ID'), "Adding single record as ID to many_many");
	}
	
	public function testRemoveByID() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->removeByID($team1->ID);
		$player1->flushCache();
		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID', 'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'), $compareTeams->column('ID'), "Removing single record as ID from many_many");
	}
	
	public function testSetByIdList() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
		$player1->Teams()->setByIdList(array($team1->ID, $team2->ID));
		$this->assertEquals(array($team1->ID, $team2->ID), $player1->Teams()->sort('Title')->column());
		$player1->Teams()->setByIdList(array($team1->ID));
		$this->assertEquals(array($team1->ID), $player1->Teams()->sort('Title')->column());
		$player1->Teams()->setByIdList(array($team2->ID));
		$this->assertEquals(array($team2->ID), $player1->Teams()->sort('Title')->column());
	}

	public function testAddingWithMultipleForeignKeys() {
		$newPlayer = new DataObjectTest_Player();
		$newPlayer->write();
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');

		$playersTeam1Team2 = DataObjectTest_Team::get()->relation('Players')->forForeignID(array($team1->ID, $team2->ID));
		$playersTeam1Team2->add($newPlayer);
		$this->assertEquals(
			array($team1->ID, $team2->ID),
			$newPlayer->Teams()->sort('Title')->column('ID')
		);
	}
	
	public function testSubtractOnAManyManyList() {
		$allList = ManyManyList::create('DataObjectTest_Player', 'DataObjectTest_Team_Players','DataObjectTest_PlayerID', 'DataObjectTest_TeamID');
		$this->assertEquals(3, $allList->count(), 'Precondition; we have all 3 players connected to a team in the list');

		$teamOneID = $this->idFromFixture('DataObjectTest_Team', 'team1');
		$teamTwoID = $this->idFromFixture('DataObjectTest_Team', 'team2');
		
		// Captain 1 belongs to one team; team1
		$captain1 = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$this->assertEquals(array($teamOneID),$captain1->Teams()->column("ID"), 'Precondition; player2 belongs to team1');
		
		// Player 2 belongs to both teams: team1, team2
		$player2 = $this->objFromFixture('DataObjectTest_Player', 'player2');
		$this->assertEquals(array($teamOneID,$teamTwoID), $player2->Teams()->sort('Title')->column('ID'), 'Precondition; player2 belongs to team1 and team2');

		// We want to find the teams for player2 where the captain does not belong to
		$teamsWithoutTheCaptain = $player2->Teams()->subtract($captain1->Teams());
		
		// Assertions
		$this->assertEquals(1,$teamsWithoutTheCaptain->count(), 'The ManyManyList should onlu contain one team');
		$this->assertEquals($teamTwoID, $teamsWithoutTheCaptain->first()->ID, 'The ManyManyList contains the wrong team');
	}
}
