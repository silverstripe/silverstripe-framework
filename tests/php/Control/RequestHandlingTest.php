<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ErrorPage\ErrorPageControllerExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Tests\RequestHandlingTest\AllowedController;
use SilverStripe\Control\Tests\RequestHandlingTest\ControllerFormWithAllowedActions;
use SilverStripe\Control\Tests\RequestHandlingTest\FieldController;
use SilverStripe\Control\Tests\RequestHandlingTest\FormActionController;
use SilverStripe\Control\Tests\RequestHandlingTest\TestController;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Director;
use SilverStripe\Forms\Form;
use SilverStripe\Security\SecurityToken;

/**
 * Tests for RequestHandler and HTTPRequest.
 * We've set up a simple URL handling model based on
 */
class RequestHandlingTest extends FunctionalTest
{
    protected static $fixture_file = null;

    protected static $illegal_extensions = array(
        // Suppress CMS error page handling
        Controller::class => array(
            ErrorPageControllerExtension::class,
        ),
        Form::class => array(
            ErrorPageControllerExtension::class,
        ),
        LeftAndMain::class => array(
            ErrorPageControllerExtension::class,
        ),
    );

    protected static $extra_controllers = [
        TestController::class,
        AllowedController::class,
        ControllerFormWithAllowedActions::class,
        FieldController::class,
        FormActionController::class
    ];

    public function getExtraRoutes()
    {
        $routes = parent::getExtraRoutes();
        return array_merge(
            $routes,
            [
                // If we don't request any variables, then the whole URL will get shifted off.
                // This is fine, but it means that the controller will have to parse the Action from the URL itself.
                'testGoodBase1' => TestController::class,

                // The double-slash indicates how much of the URL should be shifted off the stack.
                // This is important for dealing with nested request handlers appropriately.
                'testGoodBase2//$Action/$ID/$OtherID' => TestController::class,

                // By default, the entire URL will be shifted off. This creates a bit of
                // backward-incompatability, but makes the URL rules much more explicit.
                'testBadBase/$Action/$ID/$OtherID' => TestController::class,

                // Rules with an extension always default to the index() action
                'testBaseWithExtension/virtualfile.xml' => TestController::class,

                // Without the extension, the methodname should be matched
                'testBaseWithExtension//$Action/$ID/$OtherID' => TestController::class,

                // Test nested base
                'testParentBase/testChildBase//$Action/$ID/$OtherID' => TestController::class,
            ]
        );
    }

    public function testConstructedWithNullRequest()
    {
        $r = new RequestHandler();
        $this->assertInstanceOf('SilverStripe\\Control\\NullHTTPRequest', $r->getRequest());
    }

    public function testRequestHandlerChainingAllParams()
    {
        $this->markTestIncomplete();
    }

    public function testMethodCallingOnController()
    {
        /* Calling a controller works just like it always has */
        $response = Director::test("testGoodBase1");
        $this->assertEquals("This is the controller", $response->getBody());

        /* ID and OtherID are extracted from the URL and passed in $request->params. */
        $response = Director::test("testGoodBase1/method/1/2");
        $this->assertEquals("This is a method on the controller: 1, 2", $response->getBody());

        /* In addition, these values are availalbe in $controller->urlParams.  This is mainly for backward
        * compatability. */
        $response = Director::test("testGoodBase1/legacymethod/3/4");
        $this->assertEquals("\$this->urlParams can be used, for backward compatibility: 3, 4", $response->getBody());
    }

    public function testPostRequests()
    {
        /* The HTTP Request handler can trigger special behaviour for GET and POST. */
        $response = Director::test("testGoodBase1/TestForm", array("MyField" => 3), null, "POST");
        $this->assertEquals("Form posted", $response->getBody());

        $response = Director::test("testGoodBase1/TestForm");
        $this->assertEquals("Get request on form", $response->getBody());
    }

    public function testRequestHandlerChaining()
    {
        /* Request handlers can be chained, from Director to Controller to Form to FormField.  Here, we can make a get
        request on a FormField. */
        $response = Director::test("testGoodBase1/TestForm/fields/MyField");
        $this->assertEquals("MyField requested", $response->getBody());

        /* We can also make a POST request on a form field, which could be used for in-place editing, for example. */
        $response = Director::test("testGoodBase1/TestForm/fields/MyField", array("MyField" => 5));
        $this->assertEquals("MyField posted, update to 5", $response->getBody());
    }

    public function testBaseUrlPrefixed()
    {
        $this->withBaseFolder(
            '/silverstripe',
            function ($test) {
                $this->assertEquals(
                    'MyField requested',
                    Director::test('/silverstripe/testGoodBase1/TestForm/fields/MyField')->getBody()
                );

                $this->assertEquals(
                    'MyField posted, update to 5',
                    Director::test('/silverstripe/testGoodBase1/TestForm/fields/MyField', array('MyField' => 5))->getBody()
                );
            }
        );
    }

    public function testBadBase()
    {
        /* We no longer support using hacky attempting to handle URL parsing with broken rules */
        $response = Director::test("testBadBase/method/1/2");
        $this->assertNotEquals("This is a method on the controller: 1, 2", $response->getBody());

        $response = Director::test("testBadBase/TestForm", array("MyField" => 3), null, "POST");
        $this->assertNotEquals("Form posted", $response->getBody());

        $response = Director::test("testBadBase/TestForm/fields/MyField");
        $this->assertNotEquals("MyField requested", $response->getBody());
    }

    public function testBaseWithExtension()
    {
        /* Rules with an extension always default to the index() action */
        $response = Director::test("testBaseWithExtension/virtualfile.xml");
        $this->assertEquals("This is the controller", $response->getBody());

        /* Without the extension, the methodname should be matched */
        $response = Director::test("testBaseWithExtension/virtualfile");
        $this->assertEquals("This is the virtualfile method", $response->getBody());
    }

    public function testNestedBase()
    {
        /* Nested base should leave out the two parts and correctly map arguments */
        $response = Director::test("testParentBase/testChildBase/method/1/2");
        $this->assertEquals("This is a method on the controller: 1, 2", $response->getBody());
    }

    public function testInheritedUrlHandlers()
    {
        /* $url_handlers can be defined on any class, and */
        $response = Director::test("testGoodBase1/TestForm/fields/SubclassedField/something");
        $this->assertEquals("customSomething", $response->getBody());

        /* However, if the subclass' url_handlers don't match, then the parent class' url_handlers will be used */
        $response = Director::test("testGoodBase1/TestForm/fields/SubclassedField");
        $this->assertEquals("SubclassedField requested", $response->getBody());
    }

    public function testDisallowedExtendedActions()
    {
        /* Actions on an extension are allowed because they specifically provided appropriate allowed_actions items */
        $response = Director::test("testGoodBase1/otherExtendedMethod");
        $this->assertEquals("otherExtendedMethod", $response->getBody());

        /* The failoverMethod action wasn't explicitly listed and so isnt' allowed */
        $response = Director::test("testGoodBase1/failoverMethod");
        $this->assertEquals(404, $response->getStatusCode());

        /* However, on RequestHandlingTest_AllowedController it has been explicitly allowed */
        $response = Director::test("AllowedController/failoverMethod");
        $this->assertEquals("failoverMethod", $response->getBody());

        /* The action on the extension is allowed when explicitly allowed on extension,
        even if its not mentioned in controller */
        $response = Director::test("AllowedController/extendedMethod");
        $this->assertEquals(200, $response->getStatusCode());

        /* This action has been blocked by an argument to a method */
        $response = Director::test('AllowedController/blockMethod');
        $this->assertEquals(403, $response->getStatusCode());

        /* Whereas this one has been allowed by a method without an argument */
        $response = Director::test('AllowedController/allowMethod');
        $this->assertEquals('allowMethod', $response->getBody());
    }

    public function testHTTPException()
    {
        $exception = Director::test('TestController/throwexception');
        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals('This request was invalid.', $exception->getBody());

        $responseException = (Director::test('TestController/throwresponseexception'));
        $this->assertEquals(500, $responseException->getStatusCode());
        $this->assertEquals('There was an internal server error.', $responseException->getBody());
    }

    public function testHTTPError()
    {
        RequestHandlingTest\ControllerExtension::$called_error = false;
        RequestHandlingTest\ControllerExtension::$called_404_error = false;

        $response = Director::test('TestController/throwhttperror');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('This page does not exist.', $response->getBody());

        // Confirm that RequestHandlingTest\ControllerExtension::onBeforeHTTPError() called
        $this->assertTrue(RequestHandlingTest\ControllerExtension::$called_error);
        // Confirm that RequestHandlingTest\ControllerExtension::onBeforeHTTPError404() called
        $this->assertTrue(RequestHandlingTest\ControllerExtension::$called_404_error);
    }

    public function testMethodsOnParentClassesOfRequestHandlerDeclined()
    {
        $response = Director::test('testGoodBase1/getIterator');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFormActionsCanBypassAllowedActions()
    {
        SecurityToken::enable();

        $response = $this->get('FormActionController');
        $this->assertEquals(200, $response->getStatusCode());
        $tokenEls = $this->cssParser()->getBySelector('#Form_Form_SecurityID');
        $securityId = (string)$tokenEls[0]['value'];

        $data = array('action_formaction' => 1);
        $response = $this->post('FormActionController/Form', $data);
        $this->assertEquals(
            400,
            $response->getStatusCode(),
            'Should fail: Invocation through POST form handler, not contained in $allowed_actions, without CSRF token'
        );

        $data = array('action_disallowedcontrollermethod' => 1, 'SecurityID' => $securityId);
        $response = $this->post('FormActionController/Form', $data);
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Should fail: Invocation through POST form handler, controller action instead of form action,'
            . ' not contained in $allowed_actions, with CSRF token'
        );

        $data = array('action_formaction' => 1, 'SecurityID' => $securityId);
        $response = $this->post('FormActionController/Form', $data);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'formaction',
            $response->getBody(),
            'Should pass: Invocation through POST form handler, not contained in $allowed_actions, with CSRF token'
        );

        $data = array('action_controlleraction' => 1, 'SecurityID' => $securityId);
        $response = $this->post('FormActionController/Form', $data);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Should pass: Invocation through POST form handler, controller action instead of form action, contained in'
                . ' $allowed_actions, with CSRF token'
        );

        $data = array('action_formactionInAllowedActions' => 1);
        $response = $this->post('FormActionController/Form', $data);
        $this->assertEquals(
            400,
            $response->getStatusCode(),
            'Should fail: Invocation through POST form handler, contained in $allowed_actions, without CSRF token'
        );

        $data = array('action_formactionInAllowedActions' => 1, 'SecurityID' => $securityId);
        $response = $this->post('FormActionController/Form', $data);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Should pass: Invocation through POST form handler, contained in $allowed_actions, with CSRF token'
        );

        $data = array();
        $response = $this->post('FormActionController/formaction', $data);
        $this->assertEquals(
            404,
            $response->getStatusCode(),
            'Should fail: Invocation through POST URL, not contained in $allowed_actions, without CSRF token'
        );

        $data = array();
        $response = $this->post('FormActionController/formactionInAllowedActions', $data);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Should pass: Invocation of form action through POST URL, contained in $allowed_actions, without CSRF token'
        );

        $data = array('SecurityID' => $securityId);
        $response = $this->post('FormActionController/formactionInAllowedActions', $data);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Should pass: Invocation of form action through POST URL, contained in $allowed_actions, with CSRF token'
        );

        $data = array(); // CSRF protection doesnt kick in for direct requests
        $response = $this->post('FormActionController/formactionInAllowedActions', $data);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Should pass: Invocation of form action through POST URL, contained in $allowed_actions, without CSRF token'
        );

        SecurityToken::disable();
    }

    public function testAllowedActionsEnforcedOnForm()
    {
        $data = array('action_allowedformaction' => 1);
        $response = $this->post('ControllerFormWithAllowedActions/Form', $data);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('allowedformaction', $response->getBody());

        $data = array('action_disallowedformaction' => 1);
        $response = $this->post('ControllerFormWithAllowedActions/Form', $data);
        $this->assertEquals(403, $response->getStatusCode());
        // Note: Looks for a specific 403 thrown by Form->httpSubmission(), not RequestHandler->handleRequest()
        $this->assertContains('not allowed on form', $response->getBody());
    }

    public function testActionHandlingOnField()
    {
        $data = array('action_actionOnField' => 1);
        $response = $this->post('FieldController/TestForm', $data);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test method on MyField', $response->getBody());

        $data = array('action_actionNotAllowedOnField' => 1);
        $response = $this->post('FieldController/TestForm', $data);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
