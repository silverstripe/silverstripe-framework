<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ComplexTableFieldTest extends FunctionalTest {

	static $fixture_file = 'sapphire/tests/forms/ComplexTableFieldTest.yml';
	
	/**
	 * An instance of {@link Controller} used for
	 * running tests against.
	 *
	 * @var Controller object
	 */
	protected $controller;
	
	/**
	 * An instance of {@link Form} that is taken
	 * from the test controller, used for testing.
	 *
	 * @var Form object
	 */
	protected $form;

	function setUp() {
		parent::setUp();
		
		$this->controller = new ComplexTableFieldTest_Controller();
		$this->form = $this->controller->Form();
	}
	
	function testCorrectNumberOfRowsInTable() {
		$field = $this->form->dataFieldByName('Players');
		$parser = new CSSContentParser($field->FieldHolder());
		
		/* There are 2 players (rows) in the table */
		$this->assertEquals(count($parser->getBySelector('tbody tr')), 2, 'There are 2 players (rows) in the table');
		
		/* There are 2 CTF items in the DataObjectSet */
		$this->assertEquals($field->Items()->Count(), 2, 'There are 2 CTF items in the DataObjectSet');
	}
	
	function testDetailFormDisplaysWithCorrectFields() {
		$field = $this->form->dataFieldByName('Players');
		$detailForm = $field->add();
		$parser = new CSSContentParser($detailForm);
		
		/* There is a field called "Name", which is a text input */
		$this->assertNotNull($parser->getBySelector('#Name input'), 'There is a field called "Name", which is a text input');
		
		/* There is a field called "Role" - this field is the extra field for $many_many_extraFields */
		$this->assertNotNull($parser->getBySelector('#Role input'), 'There is a field called "Role" - this field is the extra field for $many_many_extraFields');
	}

	function testAddingNewPlayerWithExtraData() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "Name = 'The Awesome People'");
	
		$this->post('ComplexTableFieldTest_Controller/Form/field/Players/AddForm', array(
			'Name' => 'Bobby Joe',
			'ctf' => array(
				'extraFields' => array(
					'Role' => 'Goalie'
				),
				'ClassName' => 'ComplexTableFieldTest_Player',
				'manyManyRelation' => 'Players',
				'parentClass' => 'ComplexTableFieldTest_Team',
				'sourceID' => $team->ID
			)
		));

		/* Retrieve the new player record we created */		
		$newPlayer = DataObject::get_one('ComplexTableFieldTest_Player', "Name = 'Bobby Joe'");
		
		/* A new ComplexTableFieldTest_Player record was created, Name = "Bobby Joe" */
		$this->assertNotNull($newPlayer, 'A new ComplexTableFieldTest_Player record was created, Name = "Bobby Joe"');
		
		/* Get the many-many related Teams to the new player that were automatically linked by CTF */
		$teams = $newPlayer->getManyManyComponents('Teams');

		/* Automatic many-many relation was set correctly on the new player */		
		$this->assertEquals($teams->Count(), 1, 'Automatic many-many relation was set correctly on the new player');
		
		/* The extra fields have the correct value */
		$extraFields = $teams->getExtraData('Teams', $team->ID);
		$this->assertEquals($extraFields['Role'], 'Goalie', 'The extra fields have the correct value');
	}

}
class ComplexTableFieldTest_Controller extends Controller {
	
	function Link($action = null) {
		return "ComplexTableFieldTest_Controller/$action";
	}
	
	function Form() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "Name = 'The Awesome People'");
		
		$playersField = new ComplexTableField(
			$this,
			'Players',
			'ComplexTableFieldTest_Player',
			ComplexTableFieldTest_Player::$summary_fields,
			'getCMSFields'
		);
		
		$playersField->setParentClass('ComplexTableFieldTest_Team');
		
		$form = new Form(
			$this,
			'Form',
			new FieldSet(
				new HiddenField('ID', '', $team->ID),
				$playersField
			),
			new FieldSet(
				new FormAction('doSubmit', 'Submit')
			)
		);
		
		$form->disableSecurityToken();
		
		return $form;
	}

}
class ComplexTableFieldTest_Player extends DataObject implements TestOnly {

	public static $db = array(
		'Name' => 'Varchar(100)'
	);
	
	public static $many_many = array(
		'Teams' => 'ComplexTableFieldTest_Team'
	);
	
	public static $many_many_extraFields = array(
		'Teams' => array(
			'Role' => 'Varchar(100)'
		)
	);

}
class ComplexTableFieldTest_Team extends DataObject implements TestOnly {

	public static $db = array(
		'Name' => 'Varchar(100)'
	);

	public static $belongs_many_many = array(
		'Players' => 'ComplexTableFieldTest_Player'
	);
	
}
?>