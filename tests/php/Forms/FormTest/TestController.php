<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\SSViewer;

class TestController extends Controller implements TestOnly
{
    public function __construct()
    {
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    private static $allowed_actions = ['Form'];

    private static $url_handlers = [
        '$Action//$ID/$OtherID' => "handleAction",
    ];

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
                new CheckboxSetField('Boxes', null, ['1' => 'one', '2' => 'two']),
                new NumericField('Number'),
                TextField::create('ReadonlyField')
                    ->setReadonly(true)
                    ->setValue('This value is readonly')
            ),
            new FieldList(
                FormAction::create('doSubmit'),
                FormAction::create('doTriggerException'),
                FormAction::create('doSubmitValidationExempt'),
                FormAction::create('doSubmitActionExempt')
                    ->setValidationExempt(true)
            ),
            new RequiredFields(
                'Email',
                'SomeRequiredField'
            )
        );
        $form->setValidationExemptActions(['doSubmitValidationExempt']);
        $form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling

        return $form;
    }

    public function doSubmit(array $data, Form $form): HTTPResponse
    {
        $form->sessionMessage('Test save was successful', 'good');
        return $this->redirectBack();
    }

    public function doTriggerException(array $data, Form $form): HTTPResponse
    {
        $result = new ValidationResult();
        $result->addFieldError('Email', 'Error on Email field');
        $result->addError('Error at top of form');
        throw new ValidationException($result);
    }

    public function doSubmitValidationExempt(array $data, Form $form): HTTPResponse
    {
        $form->sessionMessage('Validation skipped', 'good');
        return $this->redirectBack();
    }

    public function doSubmitActionExempt(array $data, Form $form): HTTPResponse
    {
        $form->sessionMessage('Validation bypassed!', 'good');
        return $this->redirectBack();
    }

    public function getViewer($action = null)
    {
        return new SSViewer('BlankPage');
    }
}
