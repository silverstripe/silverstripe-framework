<?php
class GridFieldAddExistingAutocompleterTest extends FunctionalTest {

	protected static $fixture_file = 'GridFieldAddExistingAutocompleterTest.yml';

	protected $extraDataObjects = array(
		'GridFieldAddExistingAutocompleterTest_Team',
		'GridFieldAddExistingAutocompleterTest_Player',
		'GridFieldAddExistingAutocompleterTest_Cheerleader'
	);
	
	function testScaffoldSearchFields() {
		$autoCompleter = new GridFieldAddExistingAutocompleter($targetFragment = 'before', array('Test'));
		$gridFieldTest_Team = singleton('GridFieldAddExistingAutocompleterTest_Team');
		$this->assertEquals(
			$autoCompleter->scaffoldSearchFields('GridFieldAddExistingAutocompleterTest_Team'), 
			array(
				'Name:PartialMatch',
				'City:StartsWith',
				'Cheerleaders.Name:StartsWith'
			)
		);
		$this->assertEquals(
			$autoCompleter->scaffoldSearchFields('GridFieldAddExistingAutocompleterTest_Cheerleader'), 
			array(
				'Name:StartsWith'
			)
		);
	}
				
	function testSearch() {
		$team1 = $this->objFromFixture('GridFieldAddExistingAutocompleterTest_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldAddExistingAutocompleterTest_Team', 'team2');

		$response = $this->get('GridFieldAddExistingAutocompleterTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$btns = $parser->getBySelector('.ss-gridfield #action_gridfield_relationfind');

		$response = $this->post(
			'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search'
				. '/?gridfield_relationsearch=Team 2',
			array((string)$btns[0]['name'] => 1)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertEquals(1, count($result));
		$this->assertEquals(array($team2->ID => 'Team 2'), $result);
								
		$response = $this->post(
			'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/'
				. 'search/?gridfield_relationsearch=Heather',
			array((string)$btns[0]['name'] => 1)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertEquals(1, count($result), "The relational filter did not work");

		$response = $this->post(
			'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search'
				. '/?gridfield_relationsearch=Unknown',
			array((string)$btns[0]['name'] => 1)
		);
		$this->assertFalse($response->isError());
		$result = Convert::json2array($response->getBody());
		$this->assertEmpty($result, 'The output is either an empty array or boolean FALSE');
	}

	public function testAdd() {
		$this->logInWithPermission('ADMIN');
		$team1 = $this->objFromFixture('GridFieldAddExistingAutocompleterTest_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldAddExistingAutocompleterTest_Team', 'team2');

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

	private static $allowed_actions = array('Form');

	protected $template = 'BlankPage';

	public function Form() {
		$player = DataObject::get('GridFieldAddExistingAutocompleterTest_Player')->find('Email', 'player1@test.com');
		$config = GridFieldConfig::create()->addComponents(
			$relationComponent = new GridFieldAddExistingAutocompleter('before'),
			new GridFieldDataColumns()
		);
		$field = new GridField('testfield', 'testfield', $player->Teams(), $config);
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}

class GridFieldAddExistingAutocompleterTest_Team extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);

	private static $many_many = array('Players' => 'GridFieldAddExistingAutocompleterTest_Player');

	private static $has_many = array('Cheerleaders' => 'GridFieldAddExistingAutocompleterTest_Cheerleader');
	
	private static $searchable_fields = array(
		'Name',
		'City',
		'Cheerleaders.Name'
	);

	public function canView($member = null) {
		return true;
	}
}

class GridFieldAddExistingAutocompleterTest_Player extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'Email' => 'Varchar',
	);

	private static $belongs_many_many = array('Teams' => 'GridFieldAddExistingAutocompleterTest_Team');
}

class GridFieldAddExistingAutocompleterTest_Cheerleader extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $has_one = array('Team' => 'GridFieldAddExistingAutocompleterTest_Team');
}
