<?php
/**
 * @package framework
 * @subpackage tests
 */
class ComponentSetTest extends SapphireTest {

	protected static $fixture_file = 'ComponentSetTest.yml';

	protected $extraDataObjects = array(
		'ComponentSetTest_Player',
		'ComponentSetTest_Team'
	);

	public function testSetByIDListManyMany() {
		$team1 = $this->objFromFixture('ComponentSetTest_Team', 'team1');
		$player1_team1 = $this->objFromFixture('ComponentSetTest_Player', 'player1_team1');
		$player2 = $this->objFromFixture('ComponentSetTest_Player', 'player2');

		$team1->Players()->setByIdList(array(
			$player1_team1->ID,
			$player2->ID
		));
		$team1->flushCache();
		$this->assertContains(
			$player2->ID,
			$team1->Players()->column('ID'),
			'Can add new entry'
		);
		$this->assertContains(
			$player1_team1->ID,
			$team1->Players()->column('ID'),
			'Can retain existing entry'
		);

		$team1->Players()->setByIdList(array(
			$player1_team1->ID
		));
		$team1->flushCache();
		$this->assertNotContains(
			$player2->ID,
			$team1->Players()->column('ID'),
			'Can remove existing entry'
		);
		$this->assertContains(
			$player1_team1->ID,
			$team1->Players()->column('ID'),
			'Can retain existing entry'
		);

		$team1->Players()->setByIdList(array());
		$team1->flushCache();
		$this->assertEquals(0, $team1->Players()->Count(),
			'Can remove all entries by passing an empty array'
		);
	}
}

class ComponentSetTest_Player extends Member implements TestOnly {
	private static $belongs_many_many = array(
		'Teams' => 'ComponentSetTest_Team'
	);
}

class ComponentSetTest_Team extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar',
	);

	private static $many_many = array(
		'Players' => 'ComponentSetTest_Player'
	);
}
