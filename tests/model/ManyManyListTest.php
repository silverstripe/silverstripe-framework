<?php

/**
 * @package framework
 * @subpackage tests
 */
class ManyManyListTest extends SapphireTest {

	protected static $fixture_file = 'DataObjectTest.yml';

	protected $extraDataObjects = [
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
		'ManyManyListTest_ExtraFields'
	];


	public function testAddCompositedExtraFields() {
		$obj = new ManyManyListTest_ExtraFields();
		$obj->write();

		$money = new Money();
		$money->setAmount(100);
		$money->setCurrency('USD');

		// the actual test is that this does not generate an error in the sql.
		$obj->Clients()->add($obj, [
			'Worth' => $money,
			'Reference' => 'Foo'
		]);

		$check = $obj->Clients()->First();

		$this->assertEquals('Foo', $check->Reference, 'Basic scalar fields should exist');
		$this->assertInstanceOf('Money', $check->Worth, 'Composite fields should exist on the record');
		$this->assertEquals(100, $check->Worth->getAmount());
	}

	public function testCreateList() {
		$list = ManyManyList::create('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID',
			'DataObjectTest_PlayerID');
		$this->assertEquals(2, $list->count());
	}


	public function testRelationshipEmptyOnNewRecords() {
		// Relies on the fact that (unrelated) teams exist in the fixture file already
		$newPlayer = new DataObjectTest_Player(); // many_many Teams
		$this->assertEquals([], $newPlayer->Teams()->column('ID'));
	}

	public function testAddingSingleDataObjectByReference() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->add($team1);
		$player1->flushCache();

		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID',
			'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'),$compareTeams->column('ID'),
			"Adding single record as DataObject to many_many");
	}

	public function testRemovingSingleDataObjectByReference() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->remove($team1);
		$player1->flushCache();
		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID',
			'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'),$compareTeams->column('ID'),
			"Removing single record as DataObject from many_many");
	}

	public function testAddingSingleDataObjectByID() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->add($team1->ID);
		$player1->flushCache();
		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID',
			'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'), $compareTeams->column('ID'),
			"Adding single record as ID to many_many");
	}

	public function testRemoveByID() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$player1->Teams()->removeByID($team1->ID);
		$player1->flushCache();
		$compareTeams = new ManyManyList('DataObjectTest_Team','DataObjectTest_Team_Players', 'DataObjectTest_TeamID',
			'DataObjectTest_PlayerID');
		$compareTeams = $compareTeams->forForeignID($player1->ID);
		$this->assertEquals($player1->Teams()->column('ID'), $compareTeams->column('ID'),
			"Removing single record as ID from many_many");
	}

	public function testSetByIdList() {
		$player1 = $this->objFromFixture('DataObjectTest_Player', 'player1');
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
		$player1->Teams()->setByIdList([$team1->ID, $team2->ID]);
		$this->assertEquals([$team1->ID, $team2->ID], $player1->Teams()->sort('Title')->column());
		$player1->Teams()->setByIdList([$team1->ID]);
		$this->assertEquals([$team1->ID], $player1->Teams()->sort('Title')->column());
		$player1->Teams()->setByIdList([$team2->ID]);
		$this->assertEquals([$team2->ID], $player1->Teams()->sort('Title')->column());
	}

	public function testAddingWithMultipleForeignKeys() {
		$newPlayer = new DataObjectTest_Player();
		$newPlayer->write();
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');

		$playersTeam1Team2 = DataObjectTest_Team::get()->relation('Players')
			->forForeignID([$team1->ID, $team2->ID]);
		$playersTeam1Team2->add($newPlayer);
		$this->assertEquals(
			[$team1->ID, $team2->ID],
			$newPlayer->Teams()->sort('Title')->column('ID')
		);
	}

	public function testAddingExistingDoesntRemoveExtraFields() {
		$player = new DataObjectTest_Player();
		$player->write();
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');

		$team1->Players()->add($player, ['Position' => 'Captain']);
		$this->assertEquals(
			['Position' => 'Captain'],
			$team1->Players()->getExtraData('Teams', $player->ID),
			'Writes extrafields'
		);

		$team1->Players()->add($player);
		$this->assertEquals(
			['Position' => 'Captain'],
			$team1->Players()->getExtraData('Teams', $player->ID),
			'Retains extrafields on subsequent adds with NULL fields'
		);

		$team1->Players()->add($player, ['Position' => 'Defense']);
		$this->assertEquals(
			['Position' => 'Defense'],
			$team1->Players()->getExtraData('Teams', $player->ID),
			'Updates extrafields on subsequent adds with fields'
		);

		$team1->Players()->add($player, ['Position' => null]);
		$this->assertEquals(
			['Position' => null],
			$team1->Players()->getExtraData('Teams', $player->ID),
			'Allows clearing of extrafields on subsequent adds'
		);
	}

	public function testSubtractOnAManyManyList() {
		$allList = ManyManyList::create('DataObjectTest_Player', 'DataObjectTest_Team_Players',
			'DataObjectTest_PlayerID', 'DataObjectTest_TeamID');
		$this->assertEquals(3, $allList->count(),
			'Precondition; we have all 3 players connected to a team in the list');

		$teamOneID = $this->idFromFixture('DataObjectTest_Team', 'team1');
		$teamTwoID = $this->idFromFixture('DataObjectTest_Team', 'team2');

		// Captain 1 belongs to one team; team1
		$captain1 = $this->objFromFixture('DataObjectTest_Player', 'captain1');
		$this->assertEquals([$teamOneID],$captain1->Teams()->column("ID"),
			'Precondition; player2 belongs to team1');

		// Player 2 belongs to both teams: team1, team2
		$player2 = $this->objFromFixture('DataObjectTest_Player', 'player2');
		$this->assertEquals([$teamOneID,$teamTwoID], $player2->Teams()->sort('Title')->column('ID'),
			'Precondition; player2 belongs to team1 and team2');

		// We want to find the teams for player2 where the captain does not belong to
		$teamsWithoutTheCaptain = $player2->Teams()->subtract($captain1->Teams());

		// Assertions
		$this->assertEquals(1,$teamsWithoutTheCaptain->count(),
			'The ManyManyList should onlu contain one team');
		$this->assertEquals($teamTwoID, $teamsWithoutTheCaptain->first()->ID,
			'The ManyManyList contains the wrong team');
	}

	public function testRemoveAll() {
		$first = new DataObjectTest_Team();
		$first->write();

		$second = new DataObjectTest_Team();
		$second->write();

		$firstPlayers = $first->Players();
		$secondPlayers = $second->Players();

		$a = new DataObjectTest_Player();
		$a->ShirtNumber = 'a';
		$a->write();

		$b = new DataObjectTest_Player();
		$b->ShirtNumber = 'b';
		$b->write();

		$firstPlayers->add($a);
		$firstPlayers->add($b);

		$secondPlayers->add($a);
		$secondPlayers->add($b);

		$this->assertEquals(['a', 'b'], $firstPlayers->sort('ShirtNumber')->column('ShirtNumber'));
		$this->assertEquals(['a', 'b'], $secondPlayers->sort('ShirtNumber')->column('ShirtNumber'));

		$firstPlayers->removeAll();

		$this->assertEquals(0, count($firstPlayers));
		$this->assertEquals(2, count($secondPlayers));

		$firstPlayers->removeAll();

		$firstPlayers->add($a);
		$firstPlayers->add($b);

		$this->assertEquals(['a', 'b'], $firstPlayers->sort('ShirtNumber')->column('ShirtNumber'));

		$firstPlayers->filter('ShirtNumber', 'b')->removeAll();

		$this->assertEquals(['a'], $firstPlayers->column('ShirtNumber'));
		$this->assertEquals(['a', 'b'], $secondPlayers->sort('ShirtNumber')->column('ShirtNumber'));

		$this->assertNotNull(DataObjectTest_Player::get()->byID($a->ID));
		$this->assertNotNull(DataObjectTest_Player::get()->byID($b->ID));
	}

	public function testAppendExtraFieldsToQuery() {
		$list = new ManyManyList(
			'ManyManyListTest_ExtraFields',
			'ManyManyListTest_ExtraFields_Clients',
			'ManyManyListTest_ExtraFieldsID',
			'ChildID', [
				'Worth' => 'Money',
				'Reference' => 'Varchar'
			]
		);

		// ensure that ManyManyListTest_ExtraFields_Clients.ValueCurrency is
		// selected.
		$db = DB::get_conn();
		$expected = 'SELECT DISTINCT "ManyManyListTest_ExtraFields_Clients"."WorthCurrency",'
			.' "ManyManyListTest_ExtraFields_Clients"."WorthAmount", "ManyManyListTest_ExtraFields_Clients"."Reference",'
			.' "ManyManyListTest_ExtraFields"."ClassName", "ManyManyListTest_ExtraFields"."LastEdited",'
			.' "ManyManyListTest_ExtraFields"."Created", "ManyManyListTest_ExtraFields"."ID",'
			.' CASE WHEN "ManyManyListTest_ExtraFields"."ClassName" IS NOT NULL THEN'
			.' "ManyManyListTest_ExtraFields"."ClassName" ELSE '. Convert::raw2sql('ManyManyListTest_ExtraFields', true)
			.' END AS "RecordClassName" FROM "ManyManyListTest_ExtraFields" INNER JOIN'
			.' "ManyManyListTest_ExtraFields_Clients" ON'
			.' "ManyManyListTest_ExtraFields_Clients"."ManyManyListTest_ExtraFieldsID" ='
			.' "ManyManyListTest_ExtraFields"."ID"';

		$this->assertSQLEquals($expected, $list->sql($parameters));
	}


}

/**
 * @package framework
 * @subpackage tests
 */
class ManyManyListTest_ExtraFields extends DataObject implements TestOnly {

	private static $many_many = [
		'Clients' => 'ManyManyListTest_ExtraFields'
	];

	private static $belongs_many_many = [
		'WorksWith' => 'ManyManyListTest_ExtraFields'
	];

	private static $many_many_extraFields = [
		'Clients' => [
			'Reference' => 'Varchar',
			'Worth' => 'Money'
		]
	];
}
