<?php

class DataObjectCreatedEditedTest extends SapphireTest {

	protected static $fixture_file = 'DataObjectCreatedEditedTest.yml';

	public function testCreatedCanBeOverridden() {
		$player = $this->objFromFixture('Member', 'customcreated');
		$this->assertEquals(
			'1989-05-22 14:15:16',
			$player->dbObject('Created')->Format('Y-m-d H:i:s'),
			'The created date wasn\'t overriden by YML'
		);
		$newPlayer = new Member();
		$newPlayer->Created = '1989-05-22 14:15:16';
		$newPlayer->FirstName = 'NewPlayer';
		$newPlayer->write();
		$newPlayer = Member::get()->byID($newPlayer->ID);
		$this->assertEquals(
			'1989-05-22 14:15:16',
			$newPlayer->dbObject('Created')->Format('Y-m-d H:i:s'),
			'The created date wasn\'t overriden manually on new object'
		);
		$newPlayer->Created = '2002-05-22 14:15:16';
		$newPlayer->write();
		$newPlayer = Member::get()->byID($newPlayer->ID);
		$this->assertEquals(
			'2002-05-22 14:15:16',
			$newPlayer->dbObject('Created')->Format('Y-m-d H:i:s'),
			'The created date wasn\'t overriden manually on old object'
		);
	}

	public function testEditedCanBeOverridden() {
		$player = $this->objFromFixture('Member', 'customedited');
		$this->assertEquals(
			'1989-05-22 14:15:16',
			$player->dbObject('LastEdited')->Format('Y-m-d H:i:s'),
			'The created date wasn\'t overriden by YML'
		);
		$newPlayer = new Member();
		$newPlayer->LastEdited = '1989-05-22 14:15:16';
		$newPlayer->FirstName = 'NewPlayer';
		$newPlayer->write();
		$newPlayer = Member::get()->byID($newPlayer->ID);
		$this->assertEquals(
			'1989-05-22 14:15:16',
			$newPlayer->dbObject('LastEdited')->Format('Y-m-d H:i:s'),
			'The created date wasn\'t overriden manually on new object'
		);
		$newPlayer->LastEdited = '2002-05-22 14:15:16';
		$newPlayer->write();
		$newPlayer = Member::get()->byID($newPlayer->ID);
		$this->assertEquals(
			'2002-05-22 14:15:16',
			$newPlayer->dbObject('LastEdited')->Format('Y-m-d H:i:s'),
			'The created date wasn\'t overriden manually on old object'
		);
	}

}
