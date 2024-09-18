<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\MoneyField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\View\SSViewer;

class ControllerWithSpecialSubmittedValueFields extends Controller implements TestOnly
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
            'FormTest_ControllerWithSpecialSubmittedValueFields',
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
                new TextField('SomeRequiredField'),
                DateField::create('SomeDateField')
                    ->setHTML5(false)
                    ->setDateFormat('dd/MM/yyyy')
                    ->setValue('2000-01-01'),
                NumericField::create('SomeFrenchNumericField')
                    ->setHTML5(false)
                    ->setLocale('fr_FR')
                    ->setScale(4)
                    ->setValue(12345.6789),
                MoneyField::create('SomeFrenchMoneyField')
                    ->setValue('100.5 EUR')
                    ->setLocale('fr_FR')
            ),
            new FieldList(
                FormAction::create('doSubmit')
            ),
            new RequiredFields(
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

    public function getViewer($action = null)
    {
        return new SSViewer('BlankPage');
    }
}
