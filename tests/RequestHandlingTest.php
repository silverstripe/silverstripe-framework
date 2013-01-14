<?php

/**
 * Tests for RequestHandler and SS_HTTPRequest.
 * We've set up a simple URL handling model based on 
 */
class RequestHandlingTest extends FunctionalTest {
	static $fixture_file = null;
	
	// function testRequestHandlerChainingLatestParams() {
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
	
	function testRequestHandlerChainingAllParams() {
		// TODO
	}
	
	function testMethodCallingOnController() {
		/* Calling a controller works just like it always has */
		$response = Director::test("testGoodBase1");
		$this->assertEquals("This is the controller", $response->getBody());

		/* ID and OtherID are extracted from the URL and passed in $request->params. */
		$response = Director::test("testGoodBase1/method/1/2");
		$this->assertEquals("This is a method on the controller: 1, 2", $response->getBody());

		/* In addition, these values are availalbe in $controller->urlParams.  This is mainly for backward compatability. */
		$response = Director::test("testGoodBase1/legacymethod/3/4");
		$this->assertEquals("\$this->urlParams can be used, for backward compatibility: 3, 4", $response->getBody());
	}
		
	function testPostRequests() {
		/* The HTTP Request handler can trigger special behaviour for GET and POST. */
		$response = Director::test("testGoodBase1/TestForm", array("MyField" => 3), null, "POST");
		$this->assertEquals("Form posted", $response->getBody());

		$response = Director::test("testGoodBase1/TestForm");
		$this->assertEquals("Get request on form", $response->getBody());
	}

	function testRequestHandlerChaining() {
		/* Request handlers can be chained, from Director to Controller to Form to FormField.  Here, we can make a get
		request on a FormField. */
		$response = Director::test("testGoodBase1/TestForm/fields/MyField");
		$this->assertEquals("MyField requested", $response->getBody());
		
		/* We can also make a POST request on a form field, which could be used for in-place editing, for example. */
		$response = Director::test("testGoodBase1/TestForm/fields/MyField" ,array("MyField" => 5));
		$this->assertEquals("MyField posted, update to 5", $response->getBody());
	}
		
	function testBadBase() {
		/* Without a double-slash indicator in the URL, the entire URL is popped off the stack.  The controller's default
		action handlers have been designed for this to an extend: simple actions can still be called.  This is the set-up
		of URL rules written before this new request handler. */
		$response = Director::test("testBadBase/method/1/2");
		$this->assertEquals("This is a method on the controller: 1, 2", $response->getBody());

		$response = Director::test("testBadBase/TestForm", array("MyField" => 3), null, "POST");
		$this->assertEquals("Form posted", $response->getBody());
		
		/* It won't, however, let you chain requests to access methods on forms, or form fields.  In order to do that,
		you need to have a // marker in your URL parsing rule */
		$response = Director::test("testBadBase/TestForm/fields/MyField");
		$this->assertNotEquals("MyField requested", $response->getBody());
	}
	
	function testBaseWithExtension() {
		/* Rules with an extension always default to the index() action */
		$response = Director::test("testBaseWithExtension/virtualfile.xml");
		$this->assertEquals("This is the controller", $response->getBody());
		
		/* Without the extension, the methodname should be matched */
		$response = Director::test("testBaseWithExtension/virtualfile");
		$this->assertEquals("This is the virtualfile method", $response->getBody());
	}
	
	function testNestedBase() {
		/* Nested base should leave out the two parts and correctly map arguments */
		$response = Director::test("testParentBase/testChildBase/method/1/2");
		$this->assertEquals("This is a method on the controller: 1, 2", $response->getBody());
	}
	
	function testInheritedUrlHandlers() {
		/* $url_handlers can be defined on any class, and */
		$response = Director::test("testGoodBase1/TestForm/fields/SubclassedField/something");
		$this->assertEquals("customSomething", $response->getBody());

		/* However, if the subclass' url_handlers don't match, then the parent class' url_handlers will be used */
		$response = Director::test("testGoodBase1/TestForm/fields/SubclassedField");
		$this->assertEquals("SubclassedField requested", $response->getBody());
	}
	
	function testDisallowedExtendedActions() {
		/* Actions on magic methods are only accessible if explicitly allowed on the controller. */
		$response = Director::test("testGoodBase1/extendedMethod");
		$this->assertEquals(404, $response->getStatusCode());
		
		/* Actions on an extension are allowed because they specifically provided appropriate allowed_actions items */
		$response = Director::test("testGoodBase1/otherExtendedMethod");
		$this->assertEquals("otherExtendedMethod", $response->getBody());

		/* The failoverMethod action wasn't explicitly listed and so isnt' allowed */
		$response = Director::test("testGoodBase1/failoverMethod");
		$this->assertEquals(404, $response->getStatusCode());
		
		/* However, on RequestHandlingTest_AllowedController it has been explicitly allowed */
		$response = Director::test("RequestHandlingTest_AllowedController/failoverMethod");
		$this->assertEquals("failoverMethod", $response->getBody());

		/* The action on the extension has also been explicitly allowed even though it wasn't on the extension */
		$response = Director::test("RequestHandlingTest_AllowedController/extendedMethod");
		$this->assertEquals("extendedMethod", $response->getBody());
		
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
		$response = Director::test('RequestHandlingTest_Controller/throwhttperror');
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('This page does not exist.', $response->getBody());
	}
	
	public function testMethodsOnParentClassesOfRequestHandlerDeclined() {
		$response = Director::test('testGoodBase1/getIterator');
		$this->assertEquals(404, $response->getStatusCode());
	}
	
	function testFormActionsCanBypassAllowedActions() {
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
			'Should fail: Invocation through POST form handler, controller action instead of form action, not contained in $allowed_actions, with CSRF token'
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
			'Should pass: Invocation through POST form handler, controller action instead of form action, contained in $allowed_actions, with CSRF token'
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
	
	function testAllowedActionsEnforcedOnForm() {
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
	
	}
	
/**
 * Director rules for the test
 */
Director::addRules(50, array(
	// If we don't request any variables, then the whole URL will get shifted off.  This is fine, but it means that the
	// controller will have to parse the Action from the URL itself.
	'testGoodBase1' => "RequestHandlingTest_Controller",

	// The double-slash indicates how much of the URL should be shifted off the stack.  This is important for dealing
	// with nested request handlers appropriately.
	'testGoodBase2//$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",

	// By default, the entire URL will be shifted off.  This creates a bit of backward-incompatability, but makes the
	// URL rules much more explicit.
	'testBadBase/$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",
	
	// Rules with an extension always default to the index() action
	'testBaseWithExtension/virtualfile.xml' => "RequestHandlingTest_Controller",
	
	// Without the extension, the methodname should be matched
	'testBaseWithExtension//$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",
	
	// Test nested base
	'testParentBase/testChildBase//$Action/$ID/$OtherID' => "RequestHandlingTest_Controller",
));

/**
 * Controller for the test
 */
class RequestHandlingTest_Controller extends Controller implements TestOnly {
	
	static $allowed_actions = array(
		'method',
		'legacymethod',
		'virtualfile',
		'TestForm',
		'throwexception',
		'throwresponseexception',
		'throwhttperror',
	);

	static $url_handlers = array(
		// The double-slash is need here to ensure that 
		'$Action//$ID/$OtherID' => "handleAction",
	);

	static $extensions = array(
		'RequestHandlingTest_ControllerExtension',
		'RequestHandlingTest_AllowedControllerExtension',
	);
	
	function __construct() {
		$this->failover = new RequestHandlingTest_ControllerFailover();
		parent::__construct();
	}
	
	function index($request) {
		return "This is the controller";
	}

	function method($request) {
		return "This is a method on the controller: " . $request->param('ID') . ', ' . $request->param('OtherID');
	}

	function legacymethod($request) {
		return "\$this->urlParams can be used, for backward compatibility: " . $this->urlParams['ID'] . ', ' . $this->urlParams['OtherID'];
	}
	
	function virtualfile($request) {
		return "This is the virtualfile method";
	}
	
	function TestForm() {
		return new RequestHandlingTest_Form($this, "TestForm", new FieldSet(
			new RequestHandlingTest_FormField("MyField"),
			new RequestHandlingTest_SubclassedFormField("SubclassedField")
		), new FieldSet(
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

	}

class RequestHandlingTest_FormActionController extends Controller {
	
	protected $template = 'BlankPage';
	
	static $allowed_actions = array(
		'controlleraction',
		'Form',
		'formactionInAllowedActions'
		//'formaction', // left out, implicitly allowed through form action
	);
	
	function Link($action = null) {
		return Controller::join_links('RequestHandlingTest_FormActionController', $action);
	}
	
	function controlleraction($request) {
		return 'controlleraction';
	}
	
	function disallowedcontrollermethod() {
		return 'disallowedcontrollermethod';
	}
	
	function Form() {
		return new Form(
			$this, 
			"Form", 
			new FieldSet(
				new TextField("MyField")
			), 
			new FieldSet(
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
	function formaction($data, $form = null) {
		return 'formaction';
	}
	
	function formactionInAllowedActions($data, $form = null) {
		return 'formactionInAllowedActions';
	}
	}

/**
 * Simple extension for the test controller
 */
class RequestHandlingTest_ControllerExtension extends Extension {
	function extendedMethod() {
		return "extendedMethod";
	}
}

/**
 * Controller for the test
 */
class RequestHandlingTest_AllowedController extends Controller {
	static $url_handlers = array(
		// The double-slash is need here to ensure that 
		'$Action//$ID/$OtherID' => "handleAction",
	);
	
	static $allowed_actions = array(
		'failoverMethod', // part of the failover object
		'extendedMethod', // part of the RequestHandlingTest_ControllerExtension object
	);

	static $extensions = array(
		'RequestHandlingTest_ControllerExtension',
		'RequestHandlingTest_AllowedControllerExtension',
	);
	
	function __construct() {
		$this->failover = new RequestHandlingTest_ControllerFailover();
		parent::__construct();
	}
	
	function index($request) {
		return "This is the controller";
	}
}

/**
 * Simple extension for the test controller - with allowed_actions define
 */
class RequestHandlingTest_AllowedControllerExtension extends Extension {
	static $allowed_actions = array(
		'otherExtendedMethod'
	);
	
	function otherExtendedMethod() {
		return "otherExtendedMethod";
	}
}

class RequestHandlingTest_ControllerFailover extends ViewableData {
	function failoverMethod() {
		return "failoverMethod";
	}
}

/**
 * Form for the test
 */
class RequestHandlingTest_Form extends Form {
	static $url_handlers = array(
		'fields/$FieldName' => 'handleField',
		"POST " => "handleSubmission",
		"GET " => "handleGet",
	);
	
	// These are a different case from those in url_handlers to confirm that it's all case-insensitive
	static $allowed_actions = array(
		'handlesubmission',
		'handlefield',
		'handleget',
	);
	
	function handleField($request) {
		return $this->dataFieldByName($request->param('FieldName'));
	}
	
	function handleSubmission($request) {
		return "Form posted";
	}

	function handleGet($request) {
		return "Get request on form";
	}
}

class RequestHandlingTest_ControllerFormWithAllowedActions extends Controller {
	
	function Form() {
		return new RequestHandlingTest_FormWithAllowedActions(
			$this,
			'Form',
			new FieldSet(),
			new FieldSet(
				new FormAction('allowedformaction'),
				new FormAction('disallowedformaction') // disallowed through $allowed_actions in form
			)
		);
	}
}

class RequestHandlingTest_FormWithAllowedActions extends Form {

	static $allowed_actions = array(
		'allowedformaction' => 1,
		'httpSubmission' => 1, // TODO This should be an exception on the parent class
	);
	
	function allowedformaction() {
		return 'allowedformaction';
	}
	
	function disallowedformaction() {
		return 'disallowedformaction';
	}
}


/**
 * Form field for the test
 */
class RequestHandlingTest_FormField extends FormField {
	static $url_handlers = array(
		"POST " => "handleInPlaceEdit",
		'' => 'handleField',
		'$Action' => '$Action',
	);

	// These contain uppercase letters to test that allowed_actions doesn't need to be all lowercase
	static $allowed_actions = array(
		'TEST',
		'handleField',
		'handleInPLACEEDIT',
	);
	
	function test() {
		return "Test method on $this->name";
	}
	
	function handleField() {
		return "$this->name requested";
	}
	
	function handleInPlaceEdit($request) {
		return "$this->name posted, update to " . $request->postVar($this->name);
	}
}


/**
 * Form field for the test
 */
class RequestHandlingTest_SubclassedFormField extends RequestHandlingTest_FormField {
	// We have some url_handlers defined that override RequestHandlingTest_FormField handlers.
	// We will confirm that the url_handlers inherit.
	static $url_handlers = array(
		'something' => 'customSomething',
	);
	

	function customSomething() {
		return "customSomething";
	}
}
