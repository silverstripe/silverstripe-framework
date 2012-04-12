<?php
/**
 * @package framework
 * @subpackage tests
 */
class ComplexTableFieldTest extends FunctionalTest {

	static $fixture_file = 'ComplexTableFieldTest.yml';
	static $use_draft_site = true;
	
	protected $extraDataObjects = array(
		'ComplexTableFieldTest_Player',
		'ComplexTableFieldTest_Team',
		'ComplexTableFieldTest_Sponsor',
	);
	
	/**
	 * An instance of {@link Controller} used for
	 * running tests against.
	 *
	 * @var Controller object
	 */
	protected $controller;
	
	protected $autoFollowRedirection = false;
	
	function setUp() {
		parent::setUp();
		
		$this->controller = new ComplexTableFieldTest_Controller();
		$this->manyManyForm = $this->controller->ManyManyForm();
	}
	
	function testCorrectNumberOfRowsInTable() {
		$field = $this->manyManyForm->Fields()->dataFieldByName('Players');
		$parser = new CSSContentParser($field->FieldHolder());
		
		$this->assertEquals(count($parser->getBySelector('tbody tr')), 2, 'There are 2 players (rows) in the table');
		$this->assertEquals($field->Items()->Count(), 2, 'There are 2 CTF items in the SS_List');
	}
	
	function testAddingManyManyNewPlayer() {
		$this->logInWithPermission('ADMIN');
		
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "\"Name\" = 'The Awesome People'");
	
		$this->post('ComplexTableFieldTest_Controller/ManyManyForm/field/Players/AddForm', array(
			'Name' => 'Bobby Joe',
			'ctf' => array(
				'ClassName' => 'ComplexTableFieldTest_Player',
				'manyManyRelation' => 'Players',
				'parentClass' => 'ComplexTableFieldTest_Team',
				'sourceID' => $team->ID
			)
		));

		$newPlayer = DataObject::get_one('ComplexTableFieldTest_Player', "\"Name\" = 'Bobby Joe'");
		$this->assertNotNull($newPlayer, 'A new ComplexTableFieldTest_Player record was created, Name = "Bobby Joe"');
		$teams = $newPlayer->getManyManyComponents('Teams');
		$this->assertEquals($teams->Count(), 1, 'Automatic many-many relation was set correctly on the new player');
	}
	
	function testAddingHasManyData() {
		$this->logInWithPermission('ADMIN');
		
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "\"Name\" = 'The Awesome People'");
		
		$this->post('ComplexTableFieldTest_Controller/HasManyForm/field/Sponsors/AddForm', array(
			'Name' => 'Jim Beam',
			'ctf' => array(
				'ClassName' => 'ComplexTableFieldTest_Sponsor',
				'hasManyRelation' => 'Sponsors',
				'parentClass' => 'ComplexTableFieldTest_Team',
				'sourceID' => $team->ID
			)
		));

		$newSponsor = DataObject::get_one('ComplexTableFieldTest_Sponsor', "\"Name\" = 'Jim Beam'");
		$this->assertNotNull($newSponsor, 'A new ComplexTableFieldTest_Sponsor record was created, Name = "Jim Beam"');
		$this->assertEquals($newSponsor->TeamID, $team->ID, 'Automatic has-many/has-one relation was set correctly on the sponsor');
		$this->assertEquals($newSponsor->getComponent('Team')->ID, $team->ID, 'Automatic has-many/has-one relation was set correctly on the sponsor');
		
		$team = DataObject::get_by_id('ComplexTableFieldTest_Team', $team->ID);
		$sponsor = DataObject::get_by_id('ComplexTableFieldTest_Sponsor', $newSponsor->ID);
		$this->assertEquals($newSponsor->ID, $sponsor->ID, 'The sponsor is the same as the one we added');
		$foundTeam = $sponsor->getComponent('Team');
		$this->assertEquals($team->ID, $foundTeam->ID, 'The team ID matches on the other side of the relation');
	}
	
}
class ComplexTableFieldTest_Controller extends Controller {
	
	function Link($action = null) {
		return "ComplexTableFieldTest_Controller/$action";
	}
	
	function ManyManyForm() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "\"Name\" = 'The Awesome People'");
		
		$playersField = new ComplexTableField(
			$this,
			'Players',
			$team->Players(),
			ComplexTableFieldTest_Player::$summary_fields,
			'getCMSFields'
		);
		
		$form = new Form(
			$this,
			'ManyManyForm',
			new FieldList(
				new HiddenField('ID', '', $team->ID),
				$playersField
			),
			new FieldList(
				new FormAction('doSubmit', 'Submit')
			)
		);
		$form->loadDataFrom($team);
		
		$form->disableSecurityToken();
		
		return $form;
	}
	
	function HasManyForm() {
		$team = DataObject::get_one('ComplexTableFieldTest_Team', "\"Name\" = 'The Awesome People'");
		
		$sponsorsField = new ComplexTableField(
			$this,
			'Sponsors',
			$team->Sponsors(),
			ComplexTableFieldTest_Sponsor::$summary_fields,
			'getCMSFields'
		);
		
		$form = new Form(
			$this,
			'HasManyForm',
			new FieldList(
				new HiddenField('ID', '', $team->ID),
				$sponsorsField
			),
			new FieldList(
				new FormAction('doSubmit', 'Submit')
			)
		);
		$form->loadDataFrom($team);
		
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
