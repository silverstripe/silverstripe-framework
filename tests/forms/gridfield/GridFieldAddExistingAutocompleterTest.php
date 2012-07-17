<?php
class GridFieldAddExistingAutocompleterTest extends FunctionalTest {

	static $fixture_file = 'GridFieldTest.yml';

	protected $extraDataObjects = array('GridFieldTest_Team', 'GridFieldTest_Player');
	
	function testSearch() {
		$team1 = $this->objFromFixture('GridFieldTest_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldTest_Team', 'team2');

		$response = $this->get('GridFieldAddExistingAutocompleterTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$btns = $parser->getBySelector('.ss-gridfield #action_gridfield_relationfind');

		$response = $this->post(
			'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search/?gridfield_relationsearch=Team 2',
			array(
				(string)$btns[0]['name'] => 1
			)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertEquals(1, count($result));
		$this->assertEquals(array($team2->ID => 'Team 2'), $result);

		$response = $this->post(
			'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search/?gridfield_relationsearch=Unknown',
			array(
				(string)$btns[0]['name'] => 1
			)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertEmpty($result, 'The output is either an empty array or boolean FALSE');
	}

	function testAdd() {
		$this->logInWithPermission('ADMIN');
		$team1 = $this->objFromFixture('GridFieldTest_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldTest_Team', 'team2');

		$response = $this->get('GridFieldAddExistingAutocompleterTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield .ss-gridfield-items .ss-gridfield-item');
		$this->assertEquals(1, count($items));
		$this->assertEquals($team1->ID, (int)$items[0]['data-id']);

		$btns = $parser->getBySelector('.ss-gridfield #action_gridfield_relationadd');
		$response = $this->post(
			'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield',
			array(
				'relationID' => $team2->ID,
				(string)$btns[0]['name'] => 1
			)
		);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield .ss-gridfield-items .ss-gridfield-item');
		$this->assertEquals(2, count($items));
		$this->assertDOSEquals(array(
			array('ID' => (int)$items[0]['data-id']),
			array('ID' => (int)$items[1]['data-id']),
		), new ArrayList(array($team1, $team2)));
		
	}

}

class GridFieldAddExistingAutocompleterTest_Controller extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	function Form() {
		$player = DataObject::get('GridFieldTest_Player')->find('Email', 'player1@test.com');
		$config = GridFieldConfig::create()->addComponents(
			$relationComponent = new GridFieldAddExistingAutocompleter('before', 'Name'),
			new GridFieldDataColumns()
		);
		$field = new GridField('testfield', 'testfield', $player->Teams(), $config);
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}
