<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\View\SSViewer;

/**
 * @skipUpgrade
 */
class TestController extends Controller implements TestOnly
{

	private static $allowed_actions = array('Form');

	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	protected $template = 'BlankPage';

	public function Link($action = null)
	{
		return Controller::join_links(
			'FormTest_Controller',
			$this->getRequest()->latestParam('Action'),
			$this->getRequest()->latestParam('ID'),
			$action
		);
	}

	public function Form()
	{
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				new EmailField('Email'),
				new TextField('SomeRequiredField'),
				new CheckboxSetField('Boxes', null, array('1' => 'one', '2' => 'two')),
				new NumericField('Number')
			),
			new FieldList(
				FormAction::create('doSubmit'),
				FormAction::create('doSubmitValidationExempt'),
				FormAction::create('doSubmitActionExempt')
					->setValidationExempt(true)
			),
			new RequiredFields(
				'Email',
				'SomeRequiredField'
			)
		);
		$form->setValidationExemptActions(array('doSubmitValidationExempt'));
		$form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling

		return $form;
	}

	public function doSubmit($data, $form, $request)
	{
		$form->sessionMessage('Test save was successful', 'good');
		return $this->redirectBack();
	}

	public function doSubmitValidationExempt($data, $form, $request)
	{
		$form->sessionMessage('Validation skipped', 'good');
		return $this->redirectBack();
	}

	public function doSubmitActionExempt($data, $form, $request)
	{
		$form->sessionMessage('Validation bypassed!', 'good');
		return $this->redirectBack();
	}

	public function getViewer($action = null)
	{
		return new SSViewer('BlankPage');
	}

}
