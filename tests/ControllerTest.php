<?php

class ControllerTest extends SapphireTest {
	static $fixture_file = null;
	
	function testDefaultAction() {
		/* For a controller with a template, the default action will simple run that template. */
		$response = Director::test("ControllerTest_Controller/");
		$this->assertRegExp("/This is the main template. Content is 'default content'/", $response->getBody());
	}
	
	function testMethodActions() {
		/* The Action can refer to a method that is called on the object.  If a method returns an array, then it will be 
		used to customise the template data */
		$response = Director::test("ControllerTest_Controller/methodaction");
		$this->assertRegExp("/This is the main template. Content is 'methodaction content'./", $response->getBody());
		
		/* If the method just returns a string, then that will be used as the response */
		$response = Director::test("ControllerTest_Controller/stringaction");
		$this->assertRegExp("/stringaction was called./", $response->getBody());
	}
	
	function testTemplateActions() {
		/* If there is no method, it can be used to point to an alternative template. */
		$response = Director::test("ControllerTest_Controller/templateaction");
		$this->assertRegExp("/This is the template for templateaction. Content is 'default content'./", $response->getBody());
	}

	function testAllowedActions() {
		$response = Director::test("ControllerTest_SecuredController/methodaction");
		$this->assertEquals(200, $response->getStatusCode());
		
		$response = Director::test("ControllerTest_SecuredController/stringaction");
		$this->assertEquals(403, $response->getStatusCode());

		$response = Director::test("ControllerTest_SecuredController/adminonly");
		$this->assertEquals(403, $response->getStatusCode());
		
		// test that a controller without a specified allowed_actions allows actions through
		$response = Director::test('ControllerTest_UnsecuredController/stringaction');
		$this->assertEquals(200, $response->getStatusCode());
	}
	
	/**
	 * Test Controller::join_links()
	 */
	function testJoinLinks() {
		/* Controller::join_links() will reliably join two URL-segments together so that they will be appropriately parsed by the URL parser */
		$this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm", "MyForm"));
		$this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm/", "MyForm"));

		/* It will also handle appropriate combination of querystring variables */
		$this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/?flush=1", "MyForm"));
		$this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/", "MyForm?flush=1"));
		$this->assertEquals("admin/crm/MyForm?field=1&other=1", Controller::join_links("admin/crm/?field=1", "MyForm?other=1"));
		
		/* It can handle arbitrary numbers of components, and will ignore empty ones */
		$this->assertEquals("admin/crm/MyForm/", Controller::join_links("admin/", "crm", "", "MyForm/"));
		$this->assertEquals("admin/crm/MyForm/?a=1&b=2", Controller::join_links("admin/?a=1", "crm", "", "MyForm/?b=2"));
		
		/* It can also be used to attach additional get variables to a link */
		$this->assertEquals("admin/crm?flush=1", Controller::join_links("admin/crm", "?flush=1"));
		$this->assertEquals("admin/crm?existing=1&flush=1", Controller::join_links("admin/crm?existing=1", "?flush=1"));
		$this->assertEquals("admin/crm/MyForm?a=1&b=2&c=3", Controller::join_links("?a=1", "admin/crm", "?b=2", "MyForm?c=3"));
		
		/* Note, however, that it doesn't deal with duplicates very well. */
		$this->assertEquals("admin/crm?flush=1&flush=1", Controller::join_links("admin/crm?flush=1", "?flush=1"));
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

class ControllerTest_UnsecuredController extends ControllerTest_SecuredController {}