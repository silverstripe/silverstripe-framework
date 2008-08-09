<?php

class ControllerTest extends SapphireTest {
	static $fixture_file = null;
	
	function testDefaultAction() {
		/* For a controller with a template, the default action will simple run that template. */
		$response = Director::test("ControllerTest_Controller/");
		$this->assertEquals("This is the main template. Content is 'default content'.", $response->getBody());
	}
	
	function testMethodActions() {
		/* The Action can refer to a method that is called on the object.  If a method returns an array, then it will be 
		used to customise the template data */
		$response = Director::test("ControllerTest_Controller/methodaction");
		$this->assertEquals("This is the main template. Content is 'methodaction content'.", $response->getBody());
		
		/* If the method just returns a string, then that will be used as the response */
		$response = Director::test("ControllerTest_Controller/stringaction");
		$this->assertEquals("stringaction was called.", $response->getBody());
	}
	
	function testTemplateActions() {
		/* If there is no method, it can be used to point to an alternative template. */
		$response = Director::test("ControllerTest_Controller/templateaction");
		$this->assertEquals("This is the template for templateaction. Content is 'default content'.", $response->getBody());
	}

	function testAllowedActions() {
		$response = Director::test("ControllerTest_SecuredController/methodaction");
		$this->assertEquals(200, $response->getStatusCode());
		
		$response = Director::test("ControllerTest_SecuredController/stringaction");
		$this->assertEquals(403, $response->getStatusCode());

		$response = Director::test("ControllerTest_SecuredController/adminonly");
		$this->assertEquals(403, $response->getStatusCode());
	}
}

/**
 * Simple controller for testing
 */
class ControllerTest_Controller extends Controller {
	public $Content = "default content";
	
	function methodaction() {
		return array(
			"Content" => "methodaction content"
		);
	}
	
	function stringaction() {
		return "stringaction was called.";
	}
}

/**
 * Controller with an $allowed_actions value
 */
class ControllerTest_SecuredController extends Controller {
	static $allowed_actions = array(
		"methodaction",
		"adminonly" => "ADMIN",
	);
	
	public $Content = "default content";
	
	function methodaction() {
		return array(
			"Content" => "methodaction content"
		);
	}
	
	function stringaction() {
		return "stringaction was called.";
	}

	function adminonly() {
		return "You must be an admin!";
	}
}