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
		$this->manyManyForm = $this->controller->ManyManyForm();
	}
	
	function testCorrectNumberOfRowsInTable() {
		$field = $this->manyManyForm->dataFieldByName('Players');
		$parser = new CSSContentParser($field->FieldHolder());
		
		/* There are 2 players (rows) in the table */
		$this->assertEquals(count($parser->getBySelector('tbody tr')), 2, 'There are 2 players (rows) in the table');
		
		/* There are 2 CTF items in the DataObjectSet */
		$this->assertEquals($field->Items()->Count(), 2, 'There are 2 CTF items in the DataObjectSet');
	}
	
	function testAddingManyManyNewPlayer() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "Name = 'The Awesome People'");
	
		$this->post('ComplexTableFieldTest_Controller/ManyManyForm/field/Players/AddForm', array(
			'Name' => 'Bobby Joe',
			'ctf' => array(
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
	}
	
	function testAddingHasManyData() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "Name = 'The Awesome People'");
		
		$this->post('ComplexTableFieldTest_Controller/HasManyForm/field/Sponsors/AddForm', array(
			'Name' => 'Jim Beam',
			'ctf' => array(
				'ClassName' => 'ComplexTableFieldTest_Sponsor',
				'hasManyRelation' => 'Sponsors',
				'parentClass' => 'ComplexTableFieldTest_Team',
				'sourceID' => $team->ID
			)
		));

		/* Retrieve the new sponsor record we created */		
		$newSponsor = DataObject::get_one('ComplexTableFieldTest_Sponsor', "Name = 'Jim Beam'");
		
		/* A new ComplexTableFieldTest_Sponsor record was created, Name = "Jim Beam" */
		$this->assertNotNull($newSponsor, 'A new ComplexTableFieldTest_Sponsor record was created, Name = "Jim Beam"');
		
		/* Get the has-one related Team to the new sponsor that were automatically linked by CTF */
		$teamID = $newSponsor->TeamID;

		/* Automatic many-many relation was set correctly on the new player */		
		$this->assertTrue($teamID > 0, 'Automatic has-many/has-one relation was set correctly on the sponsor');

		/* The other side of the relation works as well */
		$team = DataObject::get_by_id('ComplexTableFieldTest_Team', $teamID);

		/* Let's get the Sponsors component */		
		$sponsor = $team->getComponents('Sponsors')->First();

		/* The sponsor is the same as the one we added */
		$this->assertEquals($newSponsor->ID, $sponsor->ID, 'The sponsor is the same as the one we added');
	}
	
}
class ComplexTableFieldTest_Controller extends Controller {
	
	function Link($action = null) {
		return "ComplexTableFieldTest_Controller/$action";
	}
	
	function ManyManyForm() {
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
			'ManyManyForm',
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
	
	function HasManyForm() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "Name = 'The Awesome People'");
		
		$sponsorsField = new ComplexTableField(
			$this,
			'Sponsors',
			'ComplexTableFieldTest_Sponsor',
			ComplexTableFieldTest_Sponsor::$summary_fields,
			'getCMSFields'
		);
		
		$sponsorsField->setParentClass('ComplexTableFieldTest_Team');
		
		$form = new Form(
			$this,
			'HasManyForm',
			new FieldSet(
				new HiddenField('ID', '', $team->ID),
				$sponsorsField
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
			'Role' => 'Varchar(100)',
			'Position' => "Enum('Admin,Player,Coach','Admin')",
			'DateJoined' => 'Date'
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
	
	public static $has_many = array(
		'Sponsors' => 'ComplexTableFieldTest_Sponsor'
	);
	
}
class ComplexTableFieldTest_Sponsor extends DataObject implements TestOnly {
	
	public static $db = array(
		'Name' => 'Varchar(100)'
	);
	
	public static $has_one = array(
		'Team' => 'ComplexTableFieldTest_Team'
	);

}
?>