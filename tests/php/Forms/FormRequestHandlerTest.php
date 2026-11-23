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
use SilverStripe\Forms\RequiredFields;
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

    public static function provideActionQueryString(): array
    {
        return [
            'query string cannot empty a submitted field' => [
                'postVars' => ['Password' => 'posted'],
                'actionName' => 'action_mySubmitWithData?Password=',
                'expectedValid' => true,
                'expectedData' => ['Password' => 'posted'],
            ],
            'query string cannot override a submitted field' => [
                'postVars' => ['Password' => 'posted'],
                'actionName' => 'action_mySubmitWithData?Password=fromAction',
                'expectedValid' => true,
                'expectedData' => ['Password' => 'posted'],
            ],
            'empty query string value for a required field that was not submitted fails validation' => [
                'postVars' => [],
                'actionName' => 'action_mySubmitWithData?Password=',
                'expectedValid' => false,
                'expectedData' => [],
            ],
            'query string value for a field that was not submitted is validated' => [
                'postVars' => [],
                'actionName' => 'action_mySubmitWithData?Password=fromAction',
                'expectedValid' => true,
                'expectedData' => ['Password' => 'fromAction'],
            ],
            'query string adds a value that is not a field' => [
                'postVars' => ['Password' => 'posted'],
                'actionName' => 'action_mySubmitWithData?StateID=abc',
                'expectedValid' => true,
                'expectedData' => ['StateID' => 'abc', 'Password' => 'posted'],
            ],
        ];
    }

    /**
     * @dataProvider provideActionQueryString
     */
    public function testActionQueryString(
        array $postVars,
        string $actionName,
        bool $expectedValid,
        array $expectedData
    ): void {
        $form = new TestForm(
            Controller::curr(),
            'Form',
            new FieldList(new TextField('Password')),
            new FieldList(new FormAction('mySubmitWithData')),
            new RequiredFields(['Password'])
        );
        $form->disableSecurityToken();
        $handler = new FormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, [...$postVars, $actionName => 1]);
        $request->setSession(new Session([]));
        $response = $handler->httpSubmission($request);
        $data = json_decode((string) $response->getBody(), true) ?? [];
        $this->assertSame($expectedValid, $form->getValidator()->getResult()->isValid());
        $this->assertSame($expectedData, array_intersect_key($data, ['Password' => true, 'StateID' => true]));
    }

    public function testDoesNotDispatchToNonActionFormMethod()
    {
        $form = new TestForm(
            Controller::curr(),
            'Form',
            new FieldList(),
            new FieldList()
        );
        $form->disableSecurityToken();
        $handler = new FormRequestHandler($form);
        $request = new HTTPRequest('POST', '/', null, [
            'action_setTemplate' => '1',
            'InjectedTemplate' => 'ftp://attacker.example/x.ss',
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
