<?php
class GridFieldRelationAddTest extends FunctionalTest {

	static $fixture_file = 'sapphire/tests/forms/gridfield/GridFieldTest.yml';

	protected $extraDataObjects = array('GridFieldTest_Team', 'GridFieldTest_Player');
	
	function testSearch() {
		$team1 = $this->objFromFixture('GridFieldTest_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldTest_Team', 'team2');

		$response = $this->get('GridFieldRelationAddTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$btns = $parser->getBySelector('.ss-gridfield #action_gridfield_relationfind');

		$response = $this->post(
			'GridFieldRelationAddTest_Controller/Form/field/testfield/search/Team 2',
			array(
				(string)$btns[0]['name'] => 1
			)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertEquals(1, count($result));
		$this->assertEquals(array($team2->ID => 'Team 2'), $result);

		$response = $this->post(
			'GridFieldRelationAddTest_Controller/Form/field/testfield/search/Unknown',
			array(
				(string)$btns[0]['name'] => 1
			)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertFalse($result);
	}

	function testAdd() {
		$this->logInWithPermission('ADMIN');
		$team1 = $this->objFromFixture('GridFieldTest_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldTest_Team', 'team2');

		$response = $this->get('GridFieldRelationAddTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield .ss-gridfield-items .ss-gridfield-item');
		$this->assertEquals(1, count($items));
		$this->assertEquals($team1->ID, (int)$items[0]['data-id']);

		$btns = $parser->getBySelector('.ss-gridfield #action_gridfield_relationadd');
		$response = $this->post(
			'GridFieldRelationAddTest_Controller/Form/field/testfield',
			array(
				'relationID' => $team2->ID,
				(string)$btns[0]['name'] => 1
			)
		);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield .ss-gridfield-items .ss-gridfield-item');
		$this->assertEquals(2, count($items));
		$this->assertEquals($team1->ID, (int)$items[0]['data-id']);
		$this->assertEquals($team2->ID, (int)$items[1]['data-id']);
		
	}

}

class GridFieldRelationAddTest_Controller extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	function Form() {
		$player = DataObject::get('GridFieldTest_Player')->find('Email', 'player1@test.com');
		$config = GridFieldConfig::create()->addComponents(
			$relationComponent = new GridFieldRelationAdd('Name'),
			new GridFieldDefaultColumns()
		);
		$field = new GridField('testfield', 'testfield', $player->Teams(), $config);
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}