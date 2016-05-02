<?php

/**
 * @package framework
 * @subpackage tests
 */
class CMSFormTest extends FunctionalTest {

	public function testValidationExemptActions() {
		$response = $this->get('CMSFormTest_Controller');

		$response = $this->submitForm(
			'CMSForm_Form',
			'action_doSubmit',
			array(
				'Email' => 'test@test.com'
			)
		);

		// Firstly, assert that required fields still work when not using an exempt action
		$this->assertPartialMatchBySelector(
			'#CMSForm_Form_SomeRequiredField_Holder span.required',
			array(
				'"Some Required Field" is required'
			),
			'Required fields show a notification on field when left blank'
		);

		// Re-submit the form using validation-exempt button
		$response = $this->submitForm(
			'CMSForm_Form',
			'action_doSubmitValidationExempt',
			array(
				'Email' => 'test@test.com'
			)
		);

		// The required message should be empty if validation was skipped
		$items = $this->cssParser()->getBySelector('#CMSForm_Form_SomeRequiredField_Holder span.required');
		$this->assertEmpty($items);

		// And the session message should show up is submitted successfully
		$this->assertPartialMatchBySelector(
			'#CMSForm_Form_error',
			array(
				'Validation skipped'
			),
			'Form->sessionMessage() shows up after reloading the form'
		);
	}

	public function testSetValidationExemptActions() {
		$form = $this->getStubForm();

		$form->setValidationExemptActions(array('exemptaction'));
		$exemptActions = $form->getValidationExemptActions();
		$this->assertEquals('exemptaction', $exemptActions[0]);
	}

	protected function getStubForm() {
		$form = new CMSForm(
			new CMSFormTest_Controller(),
			'CMSForm',
			new FieldList(),
			new FieldList()
		);

		return $form;
	}

}

class CMSFormTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';

	public function Link($action = null) {
		return Controller::join_links('CMSFormTest_Controller', $this->getRequest()->latestParam('Action'),
			$this->getRequest()->latestParam('ID'), $action);
	}

	public function Form() {
		$form = new CMSForm(
			$this,
			'Form',
			new FieldList(
				new EmailField('Email'),
				new TextField('SomeRequiredField'),
				new CheckboxSetField('Boxes', null, array('1'=>'one','2'=>'two'))
			),
			new FieldList(
				new FormAction('doSubmit'),
				new FormAction('doSubmitValidationExempt')
			),
			new RequiredFields(
				'Email',
				'SomeRequiredField'
			)
		);
		$form->setValidationExemptActions(array('doSubmitValidationExempt'));
		$form->setResponseNegotiator('foo'); // We aren't testing AJAX responses, so just set anything
		$form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling

		return $form;
	}

	public function doSubmit($data, $form, $request) {
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}

	public function doSubmitValidationExempt($data, $form, $request) {
		$form->sessionMessage('Validation skipped', 'good');
		return $this->redirectBack();
	}

	public function getViewer($action = null) {
		return new SSViewer('BlankPage');
	}

}
