<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class FormTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/forms/FormTest.yml';
	
	public function testLoadDataFromRequest() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new TextField('key1'),
				new TextField('namespace[key2]'),
				new TextField('namespace[key3][key4]'),
				new TextField('othernamespace[key5][key6][key7]')
			),
			new FieldSet()
		);
		
		// url would be ?key1=val1&namespace[key2]=val2&namespace[key3][key4]=val4&othernamespace[key5][key6][key7]=val7
		$requestData = array(
			'key1' => 'val1',
			'namespace' => array(
				'key2' => 'val2',
				'key3' => array(
					'key4' => 'val4',
				)
			),
			'othernamespace' => array(
				'key5' => array(
					'key6' =>array(
						'key7' => 'val7'
					)
				)
			)
		);
		
		$form->loadDataFrom($requestData);
		
		$fields = $form->Fields();
		$this->assertEquals($fields->fieldByName('key1')->Value(), 'val1');
		$this->assertEquals($fields->fieldByName('namespace[key2]')->Value(), 'val2');
		$this->assertEquals($fields->fieldByName('namespace[key3][key4]')->Value(), 'val4');
		$this->assertEquals($fields->fieldByName('othernamespace[key5][key6][key7]')->Value(), 'val7');
	}
	
	public function testLoadDataFromUnchangedHandling() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new TextField('key1'),
				new TextField('key2')
			),
			new FieldSet()
		);
		$form->loadDataFrom(array(
			'key1' => 'save',
			'key2' => 'dontsave',
			'key2_unchanged' => '1'
		));
		$this->assertEquals(
			$form->getData(), 
			array(
				'key1' => 'save',
				'key2' => null,
			),
			'loadDataFrom() doesnt save a field if a matching "<fieldname>_unchanged" flag is set'
		);
	}
	
	public function testLoadDataFromObject() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new HeaderField('MyPlayerHeader','My Player'),
				new TextField('Name'), // appears in both Player and Team
				new TextareaField('Biography'),
				new DateField('Birthday'),
				new NumericField('BirthdayYear') // dynamic property
			),
			new FieldSet()
		);
		
		$captainWithDetails = $this->objFromFixture('FormTest_Player', 'captainWithDetails');
		$form->loadDataFrom($captainWithDetails);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Captain Details',
				'Biography' => 'Bio 1',
				'Birthday' => '1982-01-01', 
				'BirthdayYear' => '1982', 
			),
			'LoadDataFrom() loads simple fields and dynamic getters'
		);

		$captainNoDetails = $this->objFromFixture('FormTest_Player', 'captainNoDetails');
		$form->loadDataFrom($captainNoDetails);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Captain No Details',
				'Biography' => null,
				'Birthday' => null, 
				'BirthdayYear' => 0, 
			),
			'LoadNonBlankDataFrom() loads only fields with values, and doesnt overwrite existing values'
		);
	}
	
	public function testLoadDataFromClearMissingFields() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new HeaderField('MyPlayerHeader','My Player'),
				new TextField('Name'), // appears in both Player and Team
				new TextareaField('Biography'),
				new DateField('Birthday'),
				new NumericField('BirthdayYear'), // dynamic property
				$unrelatedField = new TextField('UnrelatedFormField')
				//new CheckboxSetField('Teams') // relation editing
			),
			new FieldSet()
		);
		$unrelatedField->setValue("random value");
		
		$captainWithDetails = $this->objFromFixture('FormTest_Player', 'captainWithDetails');
		$captainNoDetails = $this->objFromFixture('FormTest_Player', 'captainNoDetails');
		$form->loadDataFrom($captainWithDetails);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Captain Details',
				'Biography' => 'Bio 1',
				'Birthday' => '1982-01-01', 
				'BirthdayYear' => '1982',
				'UnrelatedFormField' => 'random value',
			),
			'LoadDataFrom() doesnt overwrite fields not found in the object'
		);
		
		$captainWithDetails = $this->objFromFixture('FormTest_Player', 'captainNoDetails');
		$team2 = $this->objFromFixture('FormTest_Team', 'team2');
		$form->loadDataFrom($captainWithDetails);
		$form->loadDataFrom($team2, true);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Team 2',
				'Biography' => '',
				'Birthday' => '', 
				'BirthdayYear' => 0, 
				'UnrelatedFormField' => null,
			),
			'LoadDataFrom() overwrites fields not found in the object with $clearMissingFields=true'
		);
	}
	
	public function testFormMethodOverride() {
		$form = $this->getStubForm();
		$form->setFormMethod('GET');
		$this->assertNull($form->dataFieldByName('_method'));
		
		$form = $this->getStubForm();
		$form->setFormMethod('PUT');
		$this->assertEquals($form->dataFieldByName('_method')->Value(), 'put',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
		
		$form = $this->getStubForm();
		$form->setFormMethod('DELETE');
		$this->assertEquals($form->dataFieldByName('_method')->Value(), 'delete',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
	}
	
	function testSessionValidationMessage() {
		$this->get('FormTest_Controller');
		
		$response = $this->submitForm(
			'Form_Form',
			null,
			array(
				'Email' => 'invalid',
				// leaving out "Required" field
			)
		);
		$this->assertPartialMatchBySelector(
			'#Email span.message',
			array(
				_t('EmailField.VALIDATION', "Please enter an email address.")
			),
			'Formfield validation shows note on field if invalid'
		);
		$this->assertPartialMatchBySelector(
			'#SomeRequiredField span.required',
			array(
				sprintf(_t('Form.FIELDISREQUIRED').'.','"SomeRequiredField"')
			),
			'Required fields show a notification on field when left blank'
		);
		
	}
	
	function testSessionSuccessMessage() {
		$this->get('FormTest_Controller');
		
		$response = $this->submitForm(
			'Form_Form',
			null,
			array(
				'Email' => 'test@test.com',
				'SomeRequiredField' => 'test',
			)
		);
		$this->assertPartialMatchBySelector(
			'#Form_Form_error',
			array(
				'Test save was successful'
			),
			'Form->sessionMessage() shows up after reloading the form'
		);
	}
	
	protected function getStubForm() {
		return new Form(
			new Controller(),
			'Form',
			new FieldSet(new TextField('key1')),
			new FieldSet()
		);
	}
	
}

class FormTest_Player extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'Biography' => 'Text',
		'Birthday' => 'Date'
	);
	
	static $belongs_many_many = array(
		'Teams' => 'FormTest_Team'
	);
	
	static $has_one = array(
		'FavouriteTeam' => 'FormTest_Team', 
	);
	
	public function getBirthdayYear() {
		return ($this->Birthday) ? date('Y', strtotime($this->Birthday)) : null;
	}
	
}

class FormTest_Team extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'Region' => 'Varchar',
	);
	
	static $many_many = array(
		'Players' => 'FormTest_Player'
	);
}

class FormTest_Controller extends Controller {
	static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';
	
	function Link() {
		return Controller::join_links('FormTest_Controller', $this->request->latestParam('Action'), $this->request->latestParam('ID'));
	}
	
	function Form() {
		$form = new Form(
			$this,
			'Form',
			new FieldSet(
				new EmailField('Email'),
				new TextField('SomeRequiredField'),
				new CheckboxSetField('Boxes', null, array('1'=>'one','2'=>'two'))
			),
			new FieldSet(
				new FormAction('doSubmit')
			),
			new RequiredFields(
				'Email',
				'SomeRequiredField'
			)
		);
		return $form;
	}
	
	function doSubmit($data, $form, $request) {
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}
}

Director::addRules(50, array(
	'FormTest_Controller' => "FormTest_Controller",
));
?>