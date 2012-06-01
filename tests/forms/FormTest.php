<?php
/**
 * @package framework
 * @subpackage tests
 */
class FormTest extends FunctionalTest {
	
	static $fixture_file = 'FormTest.yml';

	protected $extraDataObjects = array(
		'FormTest_Player',
		'FormTest_Team',
	);
	
	public function testLoadDataFromRequest() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldList(
				new TextField('key1'),
				new TextField('namespace[key2]'),
				new TextField('namespace[key3][key4]'),
				new TextField('othernamespace[key5][key6][key7]')
			),
			new FieldList()
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
			new FieldList(
				new TextField('key1'),
				new TextField('key2')
			),
			new FieldList()
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
			new FieldList(
				new HeaderField('MyPlayerHeader','My Player'),
				new TextField('Name'), // appears in both Player and Team
				new TextareaField('Biography'),
				new DateField('Birthday'),
				new NumericField('BirthdayYear') // dynamic property
			),
			new FieldList()
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
			new FieldList(
				new HeaderField('MyPlayerHeader','My Player'),
				new TextField('Name'), // appears in both Player and Team
				new TextareaField('Biography'),
				new DateField('Birthday'),
				new NumericField('BirthdayYear'), // dynamic property
				$unrelatedField = new TextField('UnrelatedFormField')
				//new CheckboxSetField('Teams') // relation editing
			),
			new FieldList()
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
		$this->assertNull($form->Fields()->dataFieldByName('_method'));
		
		$form = $this->getStubForm();
		$form->setFormMethod('PUT');
		$this->assertEquals($form->Fields()->dataFieldByName('_method')->Value(), 'put',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
		
		$form = $this->getStubForm();
		$form->setFormMethod('DELETE');
		$this->assertEquals($form->Fields()->dataFieldByName('_method')->Value(), 'delete',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
	}
	
	function testSessionValidationMessage() {
		$this->get('FormTest_Controller');
		
		$response = $this->post(
			'FormTest_Controller/Form',
			array(
				'Email' => 'invalid',
				// leaving out "Required" field
			)
		);
		$this->assertPartialMatchBySelector(
			'#Email span.message',
			array(
				'Please enter an email address'
			),
			'Formfield validation shows note on field if invalid'
		);
		$this->assertPartialMatchBySelector(
			'#SomeRequiredField span.required',
			array(
				'"SomeRequiredField" is required'
			),
			'Required fields show a notification on field when left blank'
		);
		
	}
	
	function testSessionSuccessMessage() {
		$this->get('FormTest_Controller');
		
		$response = $this->post(
			'FormTest_Controller/Form',
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
	
	function testGloballyDisabledSecurityTokenInheritsToNewForm() {
		SecurityToken::enable();
		
		$form1 = $this->getStubForm();
		$this->assertInstanceOf('SecurityToken', $form1->getSecurityToken());
		
		SecurityToken::disable();
		
		$form2 = $this->getStubForm();
		$this->assertInstanceOf('NullSecurityToken', $form2->getSecurityToken());
		
		SecurityToken::enable();
	}
	
	function testDisableSecurityTokenDoesntAddTokenFormField() {
		SecurityToken::enable();
		
		$formWithToken = $this->getStubForm();
		$this->assertInstanceOf(
			'HiddenField',
			$formWithToken->Fields()->fieldByName(SecurityToken::get_default_name()),
			'Token field added by default'
		);
		
		$formWithoutToken = $this->getStubForm();
		$formWithoutToken->disableSecurityToken();
		$this->assertNull(
			$formWithoutToken->Fields()->fieldByName(SecurityToken::get_default_name()),
			'Token field not added if disableSecurityToken() is set'
		);
	}
	
	function testDisableSecurityTokenAcceptsSubmissionWithoutToken() {
		SecurityToken::enable();
		
		$response = $this->get('FormTest_ControllerWithSecurityToken');
		// can't use submitForm() as it'll automatically insert SecurityID into the POST data
		$response = $this->post(
			'FormTest_ControllerWithSecurityToken/Form',
			array(
				'Email' => 'test@test.com',
				'action_doSubmit' => 1
				// leaving out security token
			)
		);
		$this->assertEquals(400, $response->getStatusCode(), 'Submission fails without security token');
		
		$response = $this->get('FormTest_ControllerWithSecurityToken');
		$tokenEls = $this->cssParser()->getBySelector('#Form_Form_SecurityID');
		$this->assertEquals(
			1, 
			count($tokenEls), 
			'Token form field added for controller without disableSecurityToken()'
		);
		$token = (string)$tokenEls[0];
		$response = $this->submitForm(
			'Form_Form',
			null,
			array(
				'Email' => 'test@test.com',
				'SecurityID' => $token
			)
		);
		$this->assertEquals(200, $response->getStatusCode(), 'Submission suceeds with security token');
	}
	
	function testEnableSecurityToken() {
		SecurityToken::disable();
		$form = $this->getStubForm();
		$this->assertFalse($form->getSecurityToken()->isEnabled());
		$form->enableSecurityToken();
		$this->assertTrue($form->getSecurityToken()->isEnabled());
		
		SecurityToken::disable(); // restore original
	}
	
	function testDisableSecurityToken() {
		SecurityToken::enable();
		$form = $this->getStubForm();
		$this->assertTrue($form->getSecurityToken()->isEnabled());
		$form->disableSecurityToken();
		$this->assertFalse($form->getSecurityToken()->isEnabled());
		
		SecurityToken::disable(); // restore original
	}

	public function testEncType() {
		$form = $this->getStubForm();
		$this->assertEquals('application/x-www-form-urlencoded', $form->getEncType());

		$form->setEncType(Form::ENC_TYPE_MULTIPART);
		$this->assertEquals('multipart/form-data', $form->getEncType());

		$form = $this->getStubForm();
		$form->Fields()->push(new FileField(null));
		$this->assertEquals('multipart/form-data', $form->getEncType());

		$form->setEncType(Form::ENC_TYPE_URLENCODED);
		$this->assertEquals('application/x-www-form-urlencoded', $form->getEncType());
	}


	function testAttributes() {
		$form = $this->getStubForm();
		$form->setAttribute('foo', 'bar');
		$this->assertEquals('bar', $form->getAttribute('foo'));
		$attrs = $form->getAttributes();
		$this->assertArrayHasKey('foo', $attrs);
		$this->assertEquals('bar', $attrs['foo']);
	}

	function testAttributesHTML() {
		$form = $this->getStubForm();

		$form->setAttribute('foo', 'bar');
		$this->assertContains('foo="bar"', $form->getAttributesHTML());

		$form->setAttribute('foo', null);
		$this->assertNotContains('foo="bar"', $form->getAttributesHTML());

		$form->setAttribute('foo', true);
		$this->assertContains('foo="foo"', $form->getAttributesHTML());

		$form->setAttribute('one', 1);
		$form->setAttribute('two', 2);
		$form->setAttribute('three', 3);
		$this->assertNotContains('one="1"', $form->getAttributesHTML('one', 'two'));
		$this->assertNotContains('two="2"', $form->getAttributesHTML('one', 'two'));
		$this->assertContains('three="3"', $form->getAttributesHTML('one', 'two'));
	}
	
	protected function getStubForm() {
		return new Form(
			new FormTest_Controller(),
			'Form',
			new FieldList(new TextField('key1')),
			new FieldList()
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

class FormTest_Controller extends Controller implements TestOnly {
	static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';
	
	function Link($action = null) {
		return Controller::join_links('FormTest_Controller', $this->request->latestParam('Action'), $this->request->latestParam('ID'), $action);
	}
	
	function Form() {
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				new EmailField('Email'),
				new TextField('SomeRequiredField'),
				new CheckboxSetField('Boxes', null, array('1'=>'one','2'=>'two'))
			),
			new FieldList(
				new FormAction('doSubmit')
			),
			new RequiredFields(
				'Email',
				'SomeRequiredField'
			)
		);

		// Disable CSRF protection for easier form submission handling
		$form->disableSecurityToken();
		
		return $form;
	}
	
	function FormWithSecurityToken() {
		$form = new Form(
			$this,
			'FormWithSecurityToken',
			new FieldList(
				new EmailField('Email')
			),
			new FieldList(
				new FormAction('doSubmit')
			)
		);

		return $form;
	}
	
	function doSubmit($data, $form, $request) {
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}

	function getViewer($action = null) {
		return new SSViewer('BlankPage');
	}

}

class FormTest_ControllerWithSecurityToken extends Controller implements TestOnly {
	static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';
	
	function Link($action = null) {
		return Controller::join_links('FormTest_ControllerWithSecurityToken', $this->request->latestParam('Action'), $this->request->latestParam('ID'), $action);
	}
	
	function Form() {
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				new EmailField('Email')
			),
			new FieldList(
				new FormAction('doSubmit')
			)
		);

		return $form;
	}
	
	function doSubmit($data, $form, $request) {
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}

	function getViewer($action = null) {
		return new SSViewer('BlankPage');
	}
}

Config::inst()->update('Director', 'rules', array(
	'FormTest_Controller' => 'FormTest_Controller'
));

