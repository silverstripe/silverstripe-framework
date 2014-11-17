<?php

/**
 * Tests for RequestHandler and SS_HTTPRequest.
 * We've set up a simple URL handling model based on
 */
class RequestHandlingTest extends FunctionalTest {
	protected static $fixture_file = null;

	public function setUp() {
		parent::setUp();

		Config::inst()->update('Director', 'rules', array(
			// If we don't request any variables, then the whole URL will get shifted off.
			// This is fine, but it means that the controller will have to parse the Action from the URL itself.
			'testGoodBase1' => "RequestHandlingTest_Controller",

			// The double-slash indicates how much of the URL should be shifted off the stack.
			// This is important for dealing with nested request handlers appropriately.
			'testGoodBase2//$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",

			// By default, the entire URL will be shifted off. This creates a bit of
			// backward-incompatability, but makes the URL rules much more explicit.
			'testBadBase/$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",

			// Rules with an extension always default to the index() action
			'testBaseWithExtension/virtualfile.xml' => "RequestHandlingTest_Controller",

			// Without the extension, the methodname should be matched
			'testBaseWithExtension//$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",

			// Test nested base
			'testParentBase/testChildBase//$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",
		));
	}

	// public function testRequestHandlerChainingLatestParams() {
	// 	$c = new RequestHandlingTest_Controller();
	// 	$c->init();
	// 	$response = $c->handleRequest(new SS_HTTPRequest('GET', 'testGoodBase1/TestForm/fields/MyField'));
	// 	$this->assertEquals(
	// 		$c->getRequest()->latestParams(),
	// 		array(
	// 			'Action' => 'fields',
	// 			'ID' => 'MyField'
	// 		)
	// 	);
	// }

	public function testConstructedWithNullRequest() {
		$r = new RequestHandler();
		$this->assertInstanceOf('NullHTTPRequest', $r->getRequest());
	}

	public function testRequestHandlerChainingAllParams() {
		$this->markTestIncomplete();
	}

	public function testMethodCallingOnController() {
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

	public function testPostRequests() {
		/* The HTTP Request handler can trigger special behaviour for GET and POST. */
		$response = Director::test("testGoodBase1/TestForm", array("MyField" => 3), null, "POST");
		$this->assertEquals("Form posted", $response->getBody());

		$response = Director::test("testGoodBase1/TestForm");
		$this->assertEquals("Get request on form", $response->getBody());
	}

	public function testRequestHandlerChaining() {
		/* Request handlers can be chained, from Director to Controller to Form to FormField.  Here, we can make a get
		request on a FormField. */
		$response = Director::test("testGoodBase1/TestForm/fields/MyField");
		$this->assertEquals("MyField requested", $response->getBody());

		/* We can also make a POST request on a form field, which could be used for in-place editing, for example. */
		$response = Director::test("testGoodBase1/TestForm/fields/MyField", array("MyField" => 5));
		$this->assertEquals("MyField posted, update to 5", $response->getBody());
	}

	public function testBaseUrlPrefixed() {
		$this->withBaseFolder('/silverstripe', function($test) {
			$test->assertEquals(
				'MyField requested',
				Director::test('/silverstripe/testGoodBase1/TestForm/fields/MyField')->getBody()
			);

			$test->assertEquals(
				'MyField posted, update to 5',
				Director::test('/silverstripe/testGoodBase1/TestForm/fields/MyField', array('MyField' => 5))->getBody()
			);
		});
	}

	public function testBadBase() {
		/* We no longer support using hacky attempting to handle URL parsing with broken rules */
		$response = Director::test("testBadBase/method/1/2");
		$this->assertNotEquals("This is a method on the controller: 1, 2", $response->getBody());

		$response = Director::test("testBadBase/TestForm", array("MyField" => 3), null, "POST");
		$this->assertNotEquals("Form posted", $response->getBody());

		$response = Director::test("testBadBase/TestForm/fields/MyField");
		$this->assertNotEquals("MyField requested", $response->getBody());
	}

	public function testBaseWithExtension() {
		/* Rules with an extension always default to the index() action */
		$response = Director::test("testBaseWithExtension/virtualfile.xml");
		$this->assertEquals("This is the controller", $response->getBody());

		/* Without the extension, the methodname should be matched */
		$response = Director::test("testBaseWithExtension/virtualfile");
		$this->assertEquals("This is the virtualfile method", $response->getBody());
	}

	public function testNestedBase() {
		/* Nested base should leave out the two parts and correctly map arguments */
		$response = Director::test("testParentBase/testChildBase/method/1/2");
		$this->assertEquals("This is a method on the controller: 1, 2", $response->getBody());
	}

	public function testInheritedUrlHandlers() {
		/* $url_handlers can be defined on any class, and */
		$response = Director::test("testGoodBase1/TestForm/fields/SubclassedField/something");
		$this->assertEquals("customSomething", $response->getBody());

		/* However, if the subclass' url_handlers don't match, then the parent class' url_handlers will be used */
		$response = Director::test("testGoodBase1/TestForm/fields/SubclassedField");
		$this->assertEquals("SubclassedField requested", $response->getBody());
	}

	public function testDisallowedExtendedActions() {
		/* Actions on an extension are allowed because they specifically provided appropriate allowed_actions items */
		$response = Director::test("testGoodBase1/otherExtendedMethod");
		$this->assertEquals("otherExtendedMethod", $response->getBody());

		/* The failoverMethod action wasn't explicitly listed and so isnt' allowed */
		$response = Director::test("testGoodBase1/failoverMethod");
		$this->assertEquals(404, $response->getStatusCode());

		/* However, on RequestHandlingTest_AllowedController it has been explicitly allowed */
		$response = Director::test("RequestHandlingTest_AllowedController/failoverMethod");
		$this->assertEquals("failoverMethod", $response->getBody());

		/* The action on the extension is allowed when explicitly allowed on extension,
			even if its not mentioned in controller */
		$response = Director::test("RequestHandlingTest_AllowedController/extendedMethod");
		$this->assertEquals(200, $response->getStatusCode());

		/* This action has been blocked by an argument to a method */
		$response = Director::test('RequestHandlingTest_AllowedController/blockMethod');
		$this->assertEquals(403, $response->getStatusCode());

		/* Whereas this one has been allowed by a method without an argument */
		$response = Director::test('RequestHandlingTest_AllowedController/allowMethod');
		$this->assertEquals('allowMethod', $response->getBody());
	}

	public function testHTTPException() {
		$exception = Director::test('RequestHandlingTest_Controller/throwexception');
		$this->assertEquals(400, $exception->getStatusCode());
		$this->assertEquals('This request was invalid.', $exception->getBody());

		$responseException = (Director::test('RequestHandlingTest_Controller/throwresponseexception'));
		$this->assertEquals(500, $responseException->getStatusCode());
		$this->assertEquals('There was an internal server error.', $responseException->getBody());
	}

	public function testHTTPError() {
		RequestHandlingTest_ControllerExtension::$called_error = false;
		RequestHandlingTest_ControllerExtension::$called_404_error = false;

		$response = Director::test('RequestHandlingTest_Controller/throwhttperror');
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('This page does not exist.', $response->getBody());

		// Confirm that RequestHandlingTest_ControllerExtension::onBeforeHTTPError() called
		$this->assertTrue(RequestHandlingTest_ControllerExtension::$called_error);
		// Confirm that RequestHandlingTest_ControllerExtension::onBeforeHTTPError404() called
		$this->assertTrue(RequestHandlingTest_ControllerExtension::$called_404_error);
	}

	public function testMethodsOnParentClassesOfRequestHandlerDeclined() {
		$response = Director::test('testGoodBase1/getIterator');
		$this->assertEquals(404, $response->getStatusCode());
	}

	public function testFormActionsCanBypassAllowedActions() {
		SecurityToken::enable();

		$response = $this->get('RequestHandlingTest_FormActionController');
		$this->assertEquals(200, $response->getStatusCode());
		$tokenEls = $this->cssParser()->getBySelector('#Form_Form_SecurityID');
		$securityId = (string)$tokenEls[0]['value'];

		$data = array('action_formaction' => 1);
		$response = $this->post('RequestHandlingTest_FormActionController/Form', $data);
		$this->assertEquals(400, $response->getStatusCode(),
			'Should fail: Invocation through POST form handler, not contained in $allowed_actions, without CSRF token'
		);

		$data = array('action_disallowedcontrollermethod' => 1, 'SecurityID' => $securityId);
		$response = $this->post('RequestHandlingTest_FormActionController/Form', $data);
		$this->assertEquals(403, $response->getStatusCode(),
			'Should fail: Invocation through POST form handler, controller action instead of form action,'
			.' not contained in $allowed_actions, with CSRF token'
		);

		$data = array('action_formaction' => 1, 'SecurityID' => $securityId);
		$response = $this->post('RequestHandlingTest_FormActionController/Form', $data);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('formaction', $response->getBody(),
			'Should pass: Invocation through POST form handler, not contained in $allowed_actions, with CSRF token'
		);

		$data = array('action_controlleraction' => 1, 'SecurityID' => $securityId);
		$response = $this->post('RequestHandlingTest_FormActionController/Form', $data);
		$this->assertEquals(200, $response->getStatusCode(),
			'Should pass: Invocation through POST form handler, controller action instead of form action, contained in'
				. ' $allowed_actions, with CSRF token'
		);

		$data = array('action_formactionInAllowedActions' => 1);
		$response = $this->post('RequestHandlingTest_FormActionController/Form', $data);
		$this->assertEquals(400, $response->getStatusCode(),
			'Should fail: Invocation through POST form handler, contained in $allowed_actions, without CSRF token'
		);

		$data = array('action_formactionInAllowedActions' => 1, 'SecurityID' => $securityId);
		$response = $this->post('RequestHandlingTest_FormActionController/Form', $data);
		$this->assertEquals(200, $response->getStatusCode(),
			'Should pass: Invocation through POST form handler, contained in $allowed_actions, with CSRF token'
		);

		$data = array();
		$response = $this->post('RequestHandlingTest_FormActionController/formaction', $data);
		$this->assertEquals(404, $response->getStatusCode(),
			'Should fail: Invocation through POST URL, not contained in $allowed_actions, without CSRF token'
		);

		$data = array();
		$response = $this->post('RequestHandlingTest_FormActionController/formactionInAllowedActions', $data);
		$this->assertEquals(200, $response->getStatusCode(),
			'Should pass: Invocation of form action through POST URL, contained in $allowed_actions, without CSRF token'
		);

		$data = array('SecurityID' => $securityId);
		$response = $this->post('RequestHandlingTest_FormActionController/formactionInAllowedActions', $data);
		$this->assertEquals(200, $response->getStatusCode(),
			'Should pass: Invocation of form action through POST URL, contained in $allowed_actions, with CSRF token'
		);

		$data = array(); // CSRF protection doesnt kick in for direct requests
		$response = $this->post('RequestHandlingTest_FormActionController/formactionInAllowedActions', $data);
		$this->assertEquals(200, $response->getStatusCode(),
			'Should pass: Invocation of form action through POST URL, contained in $allowed_actions, without CSRF token'
		);

		SecurityToken::disable();
	}

	public function testAllowedActionsEnforcedOnForm() {
		$data = array('action_allowedformaction' => 1);
		$response = $this->post('RequestHandlingTest_ControllerFormWithAllowedActions/Form', $data);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('allowedformaction', $response->getBody());

		$data = array('action_disallowedformaction' => 1);
		$response = $this->post('RequestHandlingTest_ControllerFormWithAllowedActions/Form', $data);
		$this->assertEquals(403, $response->getStatusCode());
		// Note: Looks for a specific 403 thrown by Form->httpSubmission(), not RequestHandler->handleRequest()
		$this->assertContains('not allowed on form', $response->getBody());
	}

	public function testActionHandlingOnField() {
		$data = array('action_actionOnField' => 1);
		$response = $this->post('RequestHandlingFieldTest_Controller/TestForm', $data);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('Test method on MyField', $response->getBody());

		$data = array('action_actionNotAllowedOnField' => 1);
		$response = $this->post('RequestHandlingFieldTest_Controller/TestForm', $data);
		$this->assertEquals(404, $response->getStatusCode());
	}

}

/**
 * Controller for the test
 */
class RequestHandlingTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array(
		'method',
		'legacymethod',
		'virtualfile',
		'TestForm',
		'throwexception',
		'throwresponseexception',
		'throwhttperror',
	);

	private static $url_handlers = array(
		// The double-slash is need here to ensure that
		'$Action//$ID/$OtherID' => "handleAction",
	);

	private static $extensions = array(
		'RequestHandlingTest_ControllerExtension',
		'RequestHandlingTest_AllowedControllerExtension',
	);

	public function __construct() {
		$this->failover = new RequestHandlingTest_ControllerFailover();
		parent::__construct();
	}

	public function index($request) {
		return "This is the controller";
	}

	public function method($request) {
		return "This is a method on the controller: " . $request->param('ID') . ', ' . $request->param('OtherID');
	}

	public function legacymethod($request) {
		return "\$this->urlParams can be used, for backward compatibility: " . $this->urlParams['ID'] . ', '
			. $this->urlParams['OtherID'];
	}

	public function virtualfile($request) {
		return "This is the virtualfile method";
	}

	public function TestForm() {
		return new RequestHandlingTest_Form($this, "TestForm", new FieldList(
			new RequestHandlingTest_FormField("MyField"),
			new RequestHandlingTest_SubclassedFormField("SubclassedField")
		), new FieldList(
			new FormAction("myAction")
		));
	}

	public function throwexception() {
		throw new SS_HTTPResponse_Exception('This request was invalid.', 400);
	}

	public function throwresponseexception() {
		throw new SS_HTTPResponse_Exception(new SS_HTTPResponse('There was an internal server error.', 500));
	}

	public function throwhttperror() {
		$this->httpError(404, 'This page does not exist.');
	}

	public function getViewer($action) {
		return new SSViewer('BlankPage');
	}
}

class RequestHandlingTest_FormActionController extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	private static $allowed_actions = array(
		'controlleraction',
		'Form',
		'formactionInAllowedActions'
		//'formaction', // left out, implicitly allowed through form action
	);

	public function Link($action = null) {
		return Controller::join_links('RequestHandlingTest_FormActionController', $action);
	}

	public function controlleraction($request) {
		return 'controlleraction';
	}

	public function disallowedcontrollermethod() {
		return 'disallowedcontrollermethod';
	}

	public function Form() {
		return new Form(
			$this,
			"Form",
			new FieldList(
				new TextField("MyField")
			),
			new FieldList(
				new FormAction("formaction"),
				new FormAction('formactionInAllowedActions')
			)
		);
	}

	/**
	 * @param $data
	 * @param $form Made optional to simulate error behaviour in "live" environment
	 *  (missing arguments don't throw a fatal error there)
	 */
	public function formaction($data, $form = null) {
		return 'formaction';
	}

	public function formactionInAllowedActions($data, $form = null) {
		return 'formactionInAllowedActions';
	}

	public function getViewer($action = null) {
		return new SSViewer('BlankPage');
	}

}

/**
 * Simple extension for the test controller
 */
class RequestHandlingTest_ControllerExtension extends Extension implements TestOnly {

	public static $called_error = false;

	public static $called_404_error = false;

	private static $allowed_actions = array('extendedMethod');

	public function extendedMethod() {
		return "extendedMethod";
	}

	/**
	 * Called whenever there is an HTTP error
	 */
	public function onBeforeHTTPError() {
		self::$called_error = true;
	}

	/**
	 * Called whenever there is an 404 error
	 */
	public function onBeforeHTTPError404() {
		self::$called_404_error = true;
	}

}

/**
 * Controller for the test
 */
class RequestHandlingTest_AllowedController extends Controller implements TestOnly  {
	private static $url_handlers = array(
		// The double-slash is need here to ensure that
		'$Action//$ID/$OtherID' => "handleAction",
	);

	private static $allowed_actions = array(
		'failoverMethod', // part of the failover object
		'blockMethod' => '->provideAccess(false)',
		'allowMethod' => '->provideAccess',
	);

	private static $extensions = array(
		'RequestHandlingTest_ControllerExtension',
		'RequestHandlingTest_AllowedControllerExtension',
	);

	public function __construct() {
		$this->failover = new RequestHandlingTest_ControllerFailover();
		parent::__construct();
	}

	public function index($request) {
		return "This is the controller";
	}

	function provideAccess($access = true) {
		return $access;
	}

	function blockMethod($request) {
		return 'blockMethod';
	}

	function allowMethod($request) {
		return 'allowMethod';
	}
}

/**
 * Simple extension for the test controller - with allowed_actions define
 */
class RequestHandlingTest_AllowedControllerExtension extends Extension implements TestOnly {
	private static $allowed_actions = array(
		'otherExtendedMethod'
	);

	public function otherExtendedMethod() {
		return "otherExtendedMethod";
	}
}

class RequestHandlingTest_ControllerFailover extends ViewableData implements TestOnly {
	public function failoverMethod() {
		return "failoverMethod";
	}
}

/**
 * Form for the test
 */
class RequestHandlingTest_Form extends Form implements TestOnly {
	private static $url_handlers = array(
		'fields/$FieldName' => 'handleField',
		"POST " => "handleSubmission",
		"GET " => "handleGet",
	);

	// These are a different case from those in url_handlers to confirm that it's all case-insensitive
	private static $allowed_actions = array(
		'handlesubmission',
		'handlefield',
		'handleget',
	);

	public function handleField($request) {
		return $this->Fields()->dataFieldByName($request->param('FieldName'));
	}

	public function handleSubmission($request) {
		return "Form posted";
	}

	public function handleGet($request) {
		return "Get request on form";
	}
}

class RequestHandlingTest_ControllerFormWithAllowedActions extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	public function Form() {
		return new RequestHandlingTest_FormWithAllowedActions(
			$this,
			'Form',
			new FieldList(),
			new FieldList(
				new FormAction('allowedformaction')
			)
		);
	}
}

class RequestHandlingTest_FormWithAllowedActions extends Form implements TestOnly {

	private static $allowed_actions = array(
		'allowedformaction' => 1,
	);

	public function allowedformaction() {
		return 'allowedformaction';
	}

	public function disallowedformaction() {
		return 'disallowedformaction';
	}
}


/**
 * Form field for the test
 */
class RequestHandlingTest_FormField extends FormField implements TestOnly {
	private static $url_handlers = array(
		"POST " => "handleInPlaceEdit",
		'' => 'handleField',
		'$Action' => '$Action',
	);

	// These contain uppercase letters to test that allowed_actions doesn't need to be all lowercase
	private static $allowed_actions = array(
		'TEST',
		'handleField',
		'handleInPLACEEDIT',
	);

	public function test() {
		return "Test method on $this->name";
	}

	public function handleField() {
		return "$this->name requested";
	}

	public function handleInPlaceEdit($request) {
		return "$this->name posted, update to " . $request->postVar($this->name);
	}
}


/**
 * Form field for the test
 */
class RequestHandlingTest_SubclassedFormField extends RequestHandlingTest_FormField {

	private static $allowed_actions = array('customSomething');

	// We have some url_handlers defined that override RequestHandlingTest_FormField handlers.
	// We will confirm that the url_handlers inherit.
	private static $url_handlers = array(
		'something' => 'customSomething',
	);


	public function customSomething() {
		return "customSomething";
	}
}


/**
 * Controller for the test
 */
class RequestHandlingFieldTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array('TestForm');

	public function TestForm() {
		return new Form($this, "TestForm", new FieldList(
			new RequestHandlingTest_HandlingField("MyField")
		), new FieldList(
			new FormAction("myAction")
		));
	}
}

/**
 * Form field for the test
 */
class RequestHandlingTest_HandlingField extends FormField implements TestOnly {

	private static $allowed_actions = array(
		'actionOnField'
	);

	public function actionOnField() {
		return "Test method on $this->name";
	}

	public function actionNotAllowedOnField() {
		return "actionNotAllowedOnField on $this->name";
	}
}
