<?php

/**
 * Tests for RequestHandlingData and HTTPRequest.
 * We've set up a simple URL handling model based on 
 */
class RequestHandlingTest extends SapphireTest {
	static $fixture_file = null;
	
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
));

/**
 * Controller for the test
 */
class RequestHandlingTest_Controller extends Controller {
	static $url_handlers = array(
		// The double-slash is need here to ensure that 
		'$Action//$ID/$OtherID' => "handleAction",
	);
	
	function index($request) {
		return "This is the controller";
	}

	function method($request) {
		return "This is a method on the controller: " . $request->param('ID') . ', ' . $request->param('OtherID');
	}

	function legacymethod($request) {
		return "\$this->urlParams can be used, for backward compatibility: " . $this->urlParams['ID'] . ', ' . $this->urlParams['OtherID'];
	}
	
	function TestForm() {
		return new RequestHandlingTest_Form($this, "TestForm", new FieldSet(
			new RequestHandlingTest_FormField("MyField")
		), new FieldSet(
			new FormAction("myAction")
		));
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


/**
 * Form field for the test
 */
class RequestHandlingTest_FormField extends FormField {
	static $url_handlers = array(
		"POST " => "handleInPlaceEdit",
		'' => 'handleField',
		'$Action' => '$Action',
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

