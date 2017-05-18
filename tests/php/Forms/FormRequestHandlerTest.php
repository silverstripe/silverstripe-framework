<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormRequestHandler;
use SilverStripe\Forms\Tests\FormRequestHandlerTest\TestForm;
use SilverStripe\Forms\Tests\FormRequestHandlerTest\TestFormRequestHandler;

class FormRequestHandlerTest extends SapphireTest
{
    public function testCallsActionOnFormHandler()
    {
        $form = new TestForm(
            new Controller(),
            'Form',
            new FieldList(),
            new FieldList(new FormAction('mySubmitOnFormHandler'))
        );
        $form->disableSecurityToken();
        $handler = new TestFormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, ['action_mySubmitOnFormHandler' => 1]);
        $response = $handler->httpSubmission($request);
        $this->assertFalse($response->isError());
    }

    public function testCallsActionOnForm()
    {
        $form = new TestForm(
            new Controller(),
            'Form',
            new FieldList(),
            new FieldList(new FormAction('mySubmitOnForm'))
        );
        $form->disableSecurityToken();
        $handler = new FormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, ['action_mySubmitOnForm' => 1]);
        $response = $handler->httpSubmission($request);
        $this->assertFalse($response->isError());
    }
}
