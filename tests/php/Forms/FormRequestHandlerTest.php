<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormRequestHandler;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tests\FormRequestHandlerTest\TestForm;
use SilverStripe\Forms\Tests\FormRequestHandlerTest\TestFormRequestHandler;
use SilverStripe\Forms\TextField;

/**
 * @skipUpgrade
 */
class FormRequestHandlerTest extends SapphireTest
{
    public function testCallsActionOnFormHandler()
    {
        $form = new TestForm(
            Controller::curr(),
            'Form',
            new FieldList(),
            new FieldList(new FormAction('mySubmitOnFormHandler'))
        );
        $form->disableSecurityToken();
        $handler = new TestFormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, ['action_mySubmitOnFormHandler' => 1]);
        $request->setSession(new Session([]));
        $response = $handler->httpSubmission($request);
        $this->assertFalse($response->isError());
    }

    public function testCallsActionOnForm()
    {
        $form = new TestForm(
            Controller::curr(),
            'Form',
            new FieldList(),
            new FieldList(new FormAction('mySubmitOnForm'))
        );
        $form->disableSecurityToken();
        $handler = new FormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, ['action_mySubmitOnForm' => 1]);
        $request->setSession(new Session([]));
        $response = $handler->httpSubmission($request);
        $this->assertFalse($response->isError());
    }

    public function testValidationButtonState()
    {
        /** @var FormAction $action */
        // Save button classes
        $saveButtonDirtyClass = 'btn-primary font-icon-save';
        $saveButtonPristineClass = 'btn-outline-primary font-icon-tick';

        // Create a form and handler
        $majorActions = new CompositeField([
            new FormAction('doSave', 'Save')
        ]);
        $majorActions->setName('MajorActions');
        $form = new Form(
            new FormTest\TestController(),
            'Form',
            new FieldList(new TextField('MyField')),
            new FieldList($majorActions)
        );
        $validator = new RequiredFields('MyField');
        $form->setValidator($validator);
        $form->disableSecurityToken();
        $handler = new FormRequestHandler($form);

        // Set the save button to a pristine state
        $action = $form->Actions()->fieldByName('MajorActions')->getChildren()[0];
        $action->removeExtraClass($saveButtonDirtyClass);
        $action->addExtraClass($saveButtonPristineClass);

        // Submit the form with the required field not filled in
        // Validate that save button has a dirty class because the form failed validation
        $request = new HTTPRequest('POST', '/', null, ['MyField' => '', 'action_doSave' => 1]);
        $request->setSession(new Session([]));
        $handler->httpSubmission($request);
        $this->assertTrue(strpos($action->extraClass(), $saveButtonDirtyClass) !== false);
        $this->assertTrue(strpos($action->extraClass(), $saveButtonPristineClass) === false);
    }
}
