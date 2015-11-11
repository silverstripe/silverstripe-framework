<?php

/**
 * @package framework
 * @subpackage tests
 */
class FormTest extends FunctionalTest {

	protected static $fixture_file = 'FormTest.yml';

	protected $extraDataObjects = array(
		'FormTest_Player',
		'FormTest_Team',
	);

	public function setUp() {
		parent::setUp();

		Config::inst()->update('Director', 'rules', array(
			'FormTest_Controller' => 'FormTest_Controller'
		));
	}

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
		$form->loadDataFrom($team2, Form::MERGE_CLEAR_MISSING);
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

	public function testLoadDataFromIgnoreFalseish() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldList(
				new TextField('Biography', 'Biography', 'Custom Default')
			),
			new FieldList()
		);

		$captainNoDetails = $this->objFromFixture('FormTest_Player', 'captainNoDetails');
		$captainWithDetails = $this->objFromFixture('FormTest_Player', 'captainWithDetails');

		$form->loadDataFrom($captainNoDetails, Form::MERGE_IGNORE_FALSEISH);
		$this->assertEquals(
			$form->getData(),
			array('Biography' => 'Custom Default'),
			'LoadDataFrom() doesn\'t overwrite fields when MERGE_IGNORE_FALSEISH set and values are false-ish'
		);

		$form->loadDataFrom($captainWithDetails, Form::MERGE_IGNORE_FALSEISH);
		$this->assertEquals(
			$form->getData(),
			array('Biography' => 'Bio 1'),
			'LoadDataFrom() does overwrite fields when MERGE_IGNORE_FALSEISH set and values arent false-ish'
		);
	}

	public function testFormMethodOverride() {
		$form = $this->getStubForm();
		$form->setFormMethod('GET');
		$this->assertNull($form->Fields()->dataFieldByName('_method'));

		$form = $this->getStubForm();
		$form->setFormMethod('PUT');
		$this->assertEquals($form->Fields()->dataFieldByName('_method')->Value(), 'PUT',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'POST',
			'PUT override in forms has POST in <form> tag'
		);

		$form = $this->getStubForm();
		$form->setFormMethod('DELETE');
		$this->assertEquals($form->Fields()->dataFieldByName('_method')->Value(), 'DELETE',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'POST',
			'PUT override in forms has POST in <form> tag'
		);
	}

	public function testSessionValidationMessage() {
		$this->get('FormTest_Controller');

		$response = $this->post(
			'FormTest_Controller/Form',
			array(
				'Email' => 'invalid',
				'Number' => '<a href="http://mysite.com">link</a>' // XSS attempt
				// leaving out "Required" field
			)
		);

		$this->assertPartialMatchBySelector(
			'#Form_Form_Email_Holder span.message',
			array(
				'Please enter an email address'
			),
			'Formfield validation shows note on field if invalid'
		);
		$this->assertPartialMatchBySelector(
			'#Form_Form_SomeRequiredField_Holder span.required',
			array(
				'"Some Required Field" is required'
			),
			'Required fields show a notification on field when left blank'
		);

		$this->assertContains(
			'&#039;&lt;a href=&quot;http://mysite.com&quot;&gt;link&lt;/a&gt;&#039; is not a number, only numbers can be accepted for this field',
			$response->getBody(),
			"Validation messages are safely XML encoded"
		);
		$this->assertNotContains(
			'<a href="http://mysite.com">link</a>',
			$response->getBody(),
			"Unsafe content is not emitted directly inside the response body"
		);
	}

	public function testSessionSuccessMessage() {
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

	public function testGloballyDisabledSecurityTokenInheritsToNewForm() {
		SecurityToken::enable();

		$form1 = $this->getStubForm();
		$this->assertInstanceOf('SecurityToken', $form1->getSecurityToken());

		SecurityToken::disable();

		$form2 = $this->getStubForm();
		$this->assertInstanceOf('NullSecurityToken', $form2->getSecurityToken());

		SecurityToken::enable();
	}

	public function testDisableSecurityTokenDoesntAddTokenFormField() {
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

	public function testDisableSecurityTokenAcceptsSubmissionWithoutToken() {
		SecurityToken::enable();
		$expectedToken = SecurityToken::inst()->getValue();
		
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

		// Generate a new token which doesn't match the current one
		$generator = new RandomGenerator();
		$invalidToken = $generator->randomToken('sha1');
		$this->assertNotEquals($invalidToken, $expectedToken);

		// Test token with request
		$response = $this->get('FormTest_ControllerWithSecurityToken');
		$response = $this->post(
			'FormTest_ControllerWithSecurityToken/Form',
			array(
				'Email' => 'test@test.com',
				'action_doSubmit' => 1,
				'SecurityID' => $invalidToken
			)
		);
		$this->assertEquals(200, $response->getStatusCode(), 'Submission reloads form if security token invalid');
		$this->assertTrue(
			stripos($response->getBody(), 'name="SecurityID" value="'.$expectedToken.'"') !== false,
			'Submission reloads with correct security token after failure'
		);
		$this->assertTrue(
			stripos($response->getBody(), 'name="SecurityID" value="'.$invalidToken.'"') === false,
			'Submission reloads without incorrect security token after failure'
		);

		$matched = $this->cssParser()->getBySelector('#Form_Form_Email');
		$attrs = $matched[0]->attributes();
		$this->assertEquals('test@test.com', (string)$attrs['value'], 'Submitted data is preserved');

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

	public function testStrictFormMethodChecking() {
		$response = $this->get('FormTest_ControllerWithStrictPostCheck');
		$response = $this->get(
			'FormTest_ControllerWithStrictPostCheck/Form/?Email=test@test.com&action_doSubmit=1'
		);
		$this->assertEquals(405, $response->getStatusCode(), 'Submission fails with wrong method');

		$response = $this->get('FormTest_ControllerWithStrictPostCheck');
		$response = $this->post(
			'FormTest_ControllerWithStrictPostCheck/Form',
			array(
				'Email' => 'test@test.com',
				'action_doSubmit' => 1
			)
		);
		$this->assertEquals(200, $response->getStatusCode(), 'Submission succeeds with correct method');
	}

	public function testEnableSecurityToken() {
		SecurityToken::disable();
		$form = $this->getStubForm();
		$this->assertFalse($form->getSecurityToken()->isEnabled());
		$form->enableSecurityToken();
		$this->assertTrue($form->getSecurityToken()->isEnabled());

		SecurityToken::disable(); // restore original
	}

	public function testDisableSecurityToken() {
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

	public function testAddExtraClass() {
		$form = $this->getStubForm();
		$form->addExtraClass('class1');
		$form->addExtraClass('class2');
		$this->assertStringEndsWith('class1 class2', $form->extraClass());
	}

	public function testRemoveExtraClass() {
		$form = $this->getStubForm();
		$form->addExtraClass('class1');
		$form->addExtraClass('class2');
		$this->assertStringEndsWith('class1 class2', $form->extraClass());
		$form->removeExtraClass('class1');
		$this->assertStringEndsWith('class2', $form->extraClass());
	}

	public function testAddManyExtraClasses() {
		$form = $this->getStubForm();
		//test we can split by a range of spaces and tabs
		$form->addExtraClass('class1 class2     class3	class4		class5');
		$this->assertStringEndsWith(
			'class1 class2 class3 class4 class5',
			$form->extraClass()
		);
		//test that duplicate classes don't get added
		$form->addExtraClass('class1 class2');
		$this->assertStringEndsWith(
			'class1 class2 class3 class4 class5',
			$form->extraClass()
		);
	}

	public function testRemoveManyExtraClasses() {
		$form = $this->getStubForm();
		$form->addExtraClass('class1 class2     class3	class4		class5');
		//test we can remove a single class we just added
		$form->removeExtraClass('class3');
		$this->assertStringEndsWith(
			'class1 class2 class4 class5',
			$form->extraClass()
		);
		//check we can remove many classes at once
		$form->removeExtraClass('class1 class5');
		$this->assertStringEndsWith(
			'class2 class4',
			$form->extraClass()
		);
		//check that removing a dud class is fine
		$form->removeExtraClass('dudClass');
		$this->assertStringEndsWith(
			'class2 class4',
			$form->extraClass()
		);
	}

	public function testDefaultClasses() {
		Config::nest();

		Config::inst()->update('Form', 'default_classes', array(
			'class1',
		));

		$form = $this->getStubForm();

		$this->assertContains('class1', $form->extraClass(), 'Class list does not contain expected class');

		Config::inst()->update('Form', 'default_classes', array(
			'class1',
			'class2',
		));

		$form = $this->getStubForm();

		$this->assertContains('class1 class2', $form->extraClass(), 'Class list does not contain expected class');

		Config::inst()->update('Form', 'default_classes', array(
			'class3',
		));

		$form = $this->getStubForm();

		$this->assertContains('class3', $form->extraClass(), 'Class list does not contain expected class');

		$form->removeExtraClass('class3');

		$this->assertNotContains('class3', $form->extraClass(), 'Class list contains unexpected class');

		Config::unnest();
	}

	public function testAttributes() {
		$form = $this->getStubForm();
		$form->setAttribute('foo', 'bar');
		$this->assertEquals('bar', $form->getAttribute('foo'));
		$attrs = $form->getAttributes();
		$this->assertArrayHasKey('foo', $attrs);
		$this->assertEquals('bar', $attrs['foo']);
	}

	public function testAttributesHTML() {
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

	function testMessageEscapeHtml() {
		$form = $this->getStubForm();
		$form->Controller()->handleRequest(new SS_HTTPRequest('GET', '/'), DataModel::inst()); // stub out request
		$form->sessionMessage('<em>Escaped HTML</em>', 'good', true);
		$parser = new CSSContentParser($form->forTemplate());
		$messageEls = $parser->getBySelector('.message');
		$this->assertContains(
			'&lt;em&gt;Escaped HTML&lt;/em&gt;',
			$messageEls[0]->asXML()
		);

		$form = $this->getStubForm();
		$form->Controller()->handleRequest(new SS_HTTPRequest('GET', '/'), DataModel::inst()); // stub out request
		$form->sessionMessage('<em>Unescaped HTML</em>', 'good', false);
		$parser = new CSSContentParser($form->forTemplate());
		$messageEls = $parser->getBySelector('.message');
		$this->assertContains(
			'<em>Unescaped HTML</em>',
			$messageEls[0]->asXML()
		);
	}

	function testFieldMessageEscapeHtml() {
		$form = $this->getStubForm();
		$form->Controller()->handleRequest(new SS_HTTPRequest('GET', '/'), DataModel::inst()); // stub out request
		$form->addErrorMessage('key1', '<em>Escaped HTML</em>', 'good', true);
		$form->setupFormErrors();
		$parser = new CSSContentParser($result = $form->forTemplate());
		$messageEls = $parser->getBySelector('#Form_Form_key1_Holder .message');
		$this->assertContains(
			'&lt;em&gt;Escaped HTML&lt;/em&gt;',
			$messageEls[0]->asXML()
		);

		$form = $this->getStubForm();
		$form->Controller()->handleRequest(new SS_HTTPRequest('GET', '/'), DataModel::inst()); // stub out request
		$form->addErrorMessage('key1', '<em>Unescaped HTML</em>', 'good', false);
		$form->setupFormErrors();
		$parser = new CSSContentParser($form->forTemplate());
		$messageEls = $parser->getBySelector('#Form_Form_key1_Holder .message');
		$this->assertContains(
			'<em>Unescaped HTML</em>',
			$messageEls[0]->asXML()
		);
	}

    public function testGetExtraFields()
    {
        $form = new FormTest_ExtraFieldsForm(
            new FormTest_Controller(),
            'Form',
            new FieldList(new TextField('key1')),
            new FieldList()
        );

        $data = array(
            'key1' => 'test',
            'ExtraFieldCheckbox' => false,
        );

        $form->loadDataFrom($data);

        $formData = $form->getData();
        $this->assertEmpty($formData['ExtraFieldCheckbox']);
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

/**
 * @package framework
 * @subpackage tests
 */
class FormTest_Player extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'Biography' => 'Text',
		'Birthday' => 'Date'
	);

	private static $belongs_many_many = array(
		'Teams' => 'FormTest_Team'
	);

	private static $has_one = array(
		'FavouriteTeam' => 'FormTest_Team',
	);

	public function getBirthdayYear() {
		return ($this->Birthday) ? date('Y', strtotime($this->Birthday)) : null;
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class FormTest_Team extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'Region' => 'Varchar',
	);

	private static $many_many = array(
		'Players' => 'FormTest_Player'
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class FormTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';

	public function Link($action = null) {
		return Controller::join_links('FormTest_Controller', $this->getRequest()->latestParam('Action'),
			$this->getRequest()->latestParam('ID'), $action);
	}

	public function Form() {
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				new EmailField('Email'),
				new TextField('SomeRequiredField'),
				new CheckboxSetField('Boxes', null, array('1'=>'one','2'=>'two')),
				new NumericField('Number')
			),
			new FieldList(
				new FormAction('doSubmit')
			),
			new RequiredFields(
				'Email',
				'SomeRequiredField'
			)
		);
		$form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling

		return $form;
	}

	public function doSubmit($data, $form, $request) {
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}

	public function getViewer($action = null) {
		return new SSViewer('BlankPage');
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class FormTest_ControllerWithSecurityToken extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';

	public function Link($action = null) {
		return Controller::join_links('FormTest_ControllerWithSecurityToken', $this->getRequest()->latestParam('Action'),
			$this->getRequest()->latestParam('ID'), $action);
	}

	public function Form() {
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

	public function doSubmit($data, $form, $request) {
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}

}

class FormTest_ControllerWithStrictPostCheck extends Controller implements TestOnly
{

    private static $allowed_actions = array('Form');

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links(
            'FormTest_ControllerWithStrictPostCheck',
            $this->request->latestParam('Action'),
            $this->request->latestParam('ID'),
            $action
        );
    }

    public function Form()
    {
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
        $form->setFormMethod('POST');
        $form->setStrictFormMethodCheck(true);
        $form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling

        return $form;
    }

    public function doSubmit($data, $form, $request)
    {
        $form->sessionMessage('Test save was successful', 'good');
        return $this->redirectBack();
    }
}

class FormTest_ExtraFieldsForm extends Form implements TestOnly {

    public function getExtraFields() {
        $fields = parent::getExtraFields();

        $fields->push(new CheckboxField('ExtraFieldCheckbox', 'Extra Field Checkbox', 1));

        return $fields;
    }

}
