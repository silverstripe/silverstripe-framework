<?php

class EmailFieldTest extends FunctionalTest {

	/**
	 * Check the php validator for email addresses. We should be checking against RFC 5322 which defines email address
	 * syntax.
	 *
	 * @TODO
	 *   - double quotes around the local part (before @) is not supported
	 *   - special chars ! # $ % & ' * + - / = ? ^ _ ` { | } ~ are all valid in local part
	 *   - special chars ()[]\;:,<> are valid in the local part if the local part is in double quotes
	 *   - "." is valid in the local part as long as its not first or last char
	 * @return void
	 */
	public function testEmailAddressSyntax() {
		$this->internalCheck("blah@blah.com", "Valid, simple", true);
		$this->internalCheck("mr.o'connor+on-toast@blah.com", "Valid, special chars", true);
		$this->internalCheck("", "Empty email", true);
		$this->internalCheck("invalid", "Invalid, simple", false);
		$this->internalCheck("invalid@name@domain.com", "Invalid, two @'s", false);
		$this->internalCheck("invalid@domain", "Invalid, domain too simple", false);
		$this->internalCheck("domain.but.no.user", "Invalid, no user part", false);
	}

	public function internalCheck($email, $checkText, $expectSuccess) {
		$field = new EmailField("MyEmail");
		$field->setValue($email);

		$val = new EmailFieldTest_Validator();
		try {
			$field->validate($val);
			// If we expect failure and processing gets here without an exception, the test failed
			$this->assertTrue($expectSuccess,$checkText . " (/$email/ passed validation, but not expected to)");
		} catch (Exception $e) {
			if ($e instanceof PHPUnit_Framework_AssertionFailedError) throw $e; // re-throw assertion failure
			else if ($expectSuccess) {
				$this->assertTrue(false,
					$checkText . ": " . $e->GetMessage() . " (/$email/ did not pass validation, but was expected to)");
			}
		}
	}

	/**
	 * Check that input type='email' fields are submitted by SimpleTest
	 *
	 * @see SimpleTagBuilder::_createInputTag()
	 */
	function testEmailFieldPopulation() {

		$this->get('EmailFieldTest_Controller');
		$this->submitForm('Form_Form', null, array(
			'Email' => 'test@test.com'
		));

		$this->assertPartialMatchBySelector('p.good',array(
			'Test save was successful'
		));
	}
}

class EmailFieldTest_Validator extends Validator {
	public function validationError($fieldName, $message, $messageType='') {
		throw new Exception($message);
	}

	public function javascript() {
	}

	public function php($data) {
	}
}

class EmailFieldTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';

	function Link($action = null) {
		return Controller::join_links(
			'EmailFieldTest_Controller',
			$this->getRequest()->latestParam('Action'),
			$this->getRequest()->latestParam('ID'),
			$action
		);
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
			),
			new RequiredFields(
				'Email'
			)
		);

		// Disable CSRF protection for easier form submission handling
		$form->disableSecurityToken();

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
