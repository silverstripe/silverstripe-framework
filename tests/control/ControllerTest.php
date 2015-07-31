<?php

class ControllerTest extends FunctionalTest {

	protected static $fixture_file = 'ControllerTest.yml';

	protected $autoFollowRedirection = false;

	protected $requiredExtensions = array(
		'ControllerTest_AccessBaseController' => array(
			'ControllerTest_AccessBaseControllerExtension'
		)
	);
	
	protected $depSettings = null;

	public function setUp() {
		parent::setUp();
		$this->depSettings = Deprecation::dump_settings();
	}

	public function tearDown() {
		Deprecation::restore_settings($this->depSettings);
		parent::tearDown();
	}

	public function testDefaultAction() {
		/* For a controller with a template, the default action will simple run that template. */
		$response = $this->get("ControllerTest_Controller/");
		$this->assertRegExp("/This is the main template. Content is 'default content'/", $response->getBody());
	}

	public function testMethodActions() {
		/* The Action can refer to a method that is called on the object.  If a method returns an array, then it
		 * will be used to customise the template data */
		$response = $this->get("ControllerTest_Controller/methodaction");
		$this->assertRegExp("/This is the main template. Content is 'methodaction content'./", $response->getBody());

		/* If the method just returns a string, then that will be used as the response */
		$response = $this->get("ControllerTest_Controller/stringaction");
		$this->assertRegExp("/stringaction was called./", $response->getBody());
	}

	public function testTemplateActions() {
		/* If there is no method, it can be used to point to an alternative template. */
		$response = $this->get("ControllerTest_Controller/templateaction");
		$this->assertRegExp("/This is the template for templateaction. Content is 'default content'./",
			$response->getBody());
	}

	public function testUndefinedActions() {
		$response = $this->get('ControllerTest_IndexSecuredController/undefinedaction');
		$this->assertInstanceOf('SS_HTTPResponse', $response);
		$this->assertEquals(404, $response->getStatusCode(), 'Undefined actions return a not found response.');
	}

	public function testAllowedActions() {
		$adminUser = $this->objFromFixture('Member', 'admin');

		$response = $this->get("ControllerTest_UnsecuredController/");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on index action without $allowed_actions on defining controller, ' .
			'when called without an action in the URL'
		);

		$response = $this->get("ControllerTest_UnsecuredController/index");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access denied on index action without $allowed_actions on defining controller, ' .
			'when called with an action in the URL'
		);

		Config::inst()->update('RequestHandler', 'require_allowed_actions', false);
		$response = $this->get("ControllerTest_UnsecuredController/index");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on index action without $allowed_actions on defining controller, ' .
			'when called with an action in the URL, and explicitly allowed through config'
		);
		Config::inst()->update('RequestHandler', 'require_allowed_actions', true);

		$response = $this->get("ControllerTest_UnsecuredController/method1");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action without $allowed_actions on defining controller, ' .
			'when called without an action in the URL'
		);

		Config::inst()->update('RequestHandler', 'require_allowed_actions', false);
		$response = $this->get("ControllerTest_UnsecuredController/method1");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on action without $allowed_actions on defining controller, ' .
			'when called without an action in the URL, and explicitly allowed through config'
		);
		Config::inst()->update('RequestHandler', 'require_allowed_actions', true);

		$response = $this->get("ControllerTest_AccessBaseController/");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on index with empty $allowed_actions on defining controller, ' .
			'when called without an action in the URL'
		);

		$response = $this->get("ControllerTest_AccessBaseController/index");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on index with empty $allowed_actions on defining controller, ' .
			'when called with an action in the URL'
		);

		$response = $this->get("ControllerTest_AccessBaseController/method1");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with empty $allowed_actions on defining controller'
		);

		$response = $this->get("ControllerTest_AccessBaseController/method2");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with empty $allowed_actions on defining controller, ' .
			'even when action is allowed in subclasses (allowed_actions don\'t inherit)'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on index with non-empty $allowed_actions on defining controller, ' .
			'even when index isn\'t specifically mentioned in there'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/method1");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action which is only defined in parent controller, ' .
			'even when action is allowed in currently called class (allowed_actions don\'t inherit)'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/method2");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on action originally defined with empty $allowed_actions on parent controller, ' .
			'because it has been redefined in the subclass'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/templateaction");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'if action is not a method but rather a template discovered by naming convention'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/templateaction");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'if action is not a method but rather a template discovered by naming convention'
		);

		$this->session()->inst_set('loggedInAs', $adminUser->ID);
		$response = $this->get("ControllerTest_AccessSecuredController/templateaction");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted for logged in admin on action with $allowed_actions on defining controller, ' .
			'if action is not a method but rather a template discovered by naming convention'
		);
		$this->session()->inst_set('loggedInAs', null);

		$response = $this->get("ControllerTest_AccessSecuredController/adminonly");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'when restricted by unmatched permission code'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/aDmiNOnlY");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'regardless of capitalization'
		);

		$response = $this->get('ControllerTest_AccessSecuredController/protectedmethod');
		$this->assertEquals(404, $response->getStatusCode(),
			"Access denied to protected method even if its listed in allowed_actions"
		);

		$this->session()->inst_set('loggedInAs', $adminUser->ID);
		$response = $this->get("ControllerTest_AccessSecuredController/adminonly");
		$this->assertEquals(200, $response->getStatusCode(),
			"Permission codes are respected when set in \$allowed_actions"
		);
		$this->session()->inst_set('loggedInAs', null);

		$response = $this->get('ControllerTest_AccessBaseController/extensionmethod1');
		$this->assertEquals(200, $response->getStatusCode(),
			"Access granted to method defined in allowed_actions on extension, " .
			"where method is also defined on extension"
		);

		$response = $this->get('ControllerTest_AccessSecuredController/extensionmethod1');
		$this->assertEquals(200, $response->getStatusCode(),
			"Access granted to method defined in allowed_actions on extension, " .
			"where method is also defined on extension, even when called in a subclass"
		);

		$response = $this->get('ControllerTest_AccessBaseController/extensionmethod2');
		$this->assertEquals(404, $response->getStatusCode(),
			"Access denied to method not defined in allowed_actions on extension, " .
			"where method is also defined on extension"
		);

		$response = $this->get('ControllerTest_IndexSecuredController/');
		$this->assertEquals(403, $response->getStatusCode(),
			"Access denied when index action is limited through allowed_actions, " .
			"and doesn't satisfy checks, and action is empty"
		);

		$response = $this->get('ControllerTest_IndexSecuredController/index');
		$this->assertEquals(403, $response->getStatusCode(),
			"Access denied when index action is limited through allowed_actions, " .
			"and doesn't satisfy checks"
		);

		$this->session()->inst_set('loggedInAs', $adminUser->ID);
		$response = $this->get('ControllerTest_IndexSecuredController/');
		$this->assertEquals(200, $response->getStatusCode(),
			"Access granted when index action is limited through allowed_actions, " .
			"and does satisfy checks"
		);
		$this->session()->inst_set('loggedInAs', null);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionMessage Wildcards (*) are no longer valid
	 */
	public function testWildcardAllowedActions() {
		Deprecation::set_enabled(true);
		$this->get('ControllerTest_AccessWildcardSecuredController');
	}

	/**
	 * Test Controller::join_links()
	 */
	public function testJoinLinks() {
		/* Controller::join_links() will reliably join two URL-segments together so that they will be
		 * appropriately parsed by the URL parser */
		$this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm", "MyForm"));
		$this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm/", "MyForm"));

		/* It will also handle appropriate combination of querystring variables */
		$this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/?flush=1", "MyForm"));
		$this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/", "MyForm?flush=1"));
		$this->assertEquals("admin/crm/MyForm?field=1&other=1",
			Controller::join_links("admin/crm/?field=1", "MyForm?other=1"));

		/* It can handle arbitrary numbers of components, and will ignore empty ones */
		$this->assertEquals("admin/crm/MyForm/", Controller::join_links("admin/", "crm", "", "MyForm/"));
		$this->assertEquals("admin/crm/MyForm/?a=1&b=2",
			Controller::join_links("admin/?a=1", "crm", "", "MyForm/?b=2"));

		/* It can also be used to attach additional get variables to a link */
		$this->assertEquals("admin/crm?flush=1", Controller::join_links("admin/crm", "?flush=1"));
		$this->assertEquals("admin/crm?existing=1&flush=1", Controller::join_links("admin/crm?existing=1", "?flush=1"));
		$this->assertEquals("admin/crm/MyForm?a=1&b=2&c=3",
			Controller::join_links("?a=1", "admin/crm", "?b=2", "MyForm?c=3"));

		// And duplicates are handled nicely
		$this->assertEquals("admin/crm?foo=2&bar=3&baz=1",
			Controller::join_links("admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3"));

		$this->assertEquals (
			'admin/action', Controller::join_links('admin/', '/', '/action'), 'Test that multiple slashes are trimmed.'
		);

		$this->assertEquals('/admin/action', Controller::join_links('/admin', 'action'));

		/* One fragment identifier is handled as you would expect */
		$this->assertEquals("my-page?arg=var#subsection", Controller::join_links("my-page#subsection", "?arg=var"));

		/* If there are multiple, it takes the last one */
		$this->assertEquals("my-page?arg=var#second-section",
			Controller::join_links("my-page#subsection", "?arg=var", "#second-section"));

		/* Does type-safe checks for zero value */
		$this->assertEquals("my-page/0", Controller::join_links("my-page", 0));
	}

	/**
	 * @covers Controller::hasAction
	 */
	public function testHasAction() {
		$controller = new ControllerTest_HasAction();
		$unsecuredController = new ControllerTest_HasAction_Unsecured();
		$securedController = new ControllerTest_AccessSecuredController();

		$this->assertFalse(
			$controller->hasAction('1'),
			'Numeric actions do not slip through.'
		);
		//$this->assertFalse(
		//	$controller->hasAction('lowercase_permission'),
		//	'Lowercase permission does not slip through.'
		//);
		$this->assertFalse(
			$controller->hasAction('undefined'),
			'undefined actions do not exist'
		);
		$this->assertTrue(
			$controller->hasAction('allowed_action'),
			'allowed actions are recognised'
		);
		$this->assertTrue(
			$controller->hasAction('template_action'),
			'action-specific templates are recognised'
		);

		$this->assertTrue (
			$unsecuredController->hasAction('defined_action'),
			'Without an allowed_actions, any defined methods are recognised as actions'
		);

		$this->assertTrue(
			$securedController->hasAction('adminonly'),
			'Method is generally visible even if its denied via allowed_actions'
		);

		$this->assertFalse(
			$securedController->hasAction('protectedmethod'),
			'Method is not visible when protected, even if its defined in allowed_actions'
		);

		$this->assertTrue(
			$securedController->hasAction('extensionmethod1'),
			'Method is visible when defined on an extension and part of allowed_actions'
		);

		$this->assertFalse(
			$securedController->hasAction('internalextensionmethod'),
			'Method is not visible when defined on an extension, but not part of allowed_actions'
		);

		$this->assertFalse(
			$securedController->hasAction('protectedextensionmethod'),
			'Method is not visible when defined on an extension, part of allowed_actions, ' .
			'but with protected visibility'
		);
	}

	/* Controller::BaseURL no longer exists, but was just a direct call to Director::BaseURL, so not sure what this
	 * code was supposed to test
	public function testBaseURL() {
		Config::inst()->update('Director', 'alternate_base_url', '/baseurl/');
		$this->assertEquals(Controller::BaseURL(), Director::BaseURL());
	}
	*/

	public function testRedirectBackByReferer() {
		$internalRelativeUrl = Controller::join_links(Director::baseURL(), '/some-url');
		$internalAbsoluteUrl = Controller::join_links(Director::absoluteBaseURL(), '/some-url');
		
		$response = $this->get('ControllerTest_Controller/redirectbacktest', null,
			array('Referer' => $internalRelativeUrl));
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'),
			"Redirects on internal relative URLs"
		);

		$response = $this->get('ControllerTest_Controller/redirectbacktest', null,
			array('Referer' => $internalAbsoluteUrl));
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'),
			"Redirects on internal absolute URLs"
		);

		$externalAbsoluteUrl = 'http://myhost.com/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest', null,
			array('Referer' => $externalAbsoluteUrl));
		$this->assertEquals(200, $response->getStatusCode(),
			"Doesn't redirect on external URLs"
		);
	}

	public function testRedirectBackByBackUrl() {
		$internalRelativeUrl = Controller::join_links(Director::baseURL(), '/some-url');
		$internalAbsoluteUrl = Controller::join_links(Director::absoluteBaseURL(), '/some-url');
		
		$response = $this->get('ControllerTest_Controller/redirectbacktest?BackURL=' . urlencode($internalRelativeUrl));
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'),
			"Redirects on internal relative URLs"
		);

		$internalAbsoluteUrl = Director::absoluteBaseURL() . '/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest?BackURL=' . urlencode($internalAbsoluteUrl));
		$this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'));
		$this->assertEquals(302, $response->getStatusCode(),
			"Redirects on internal absolute URLs"
		);

		$externalAbsoluteUrl = 'http://myhost.com/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest?BackURL=' . urlencode($externalAbsoluteUrl));
		$this->assertEquals(200, $response->getStatusCode(),
			"Doesn't redirect on external URLs"
		);
	}

	public function testSubActions() {
		/* If a controller action returns another controller, ensure that the $action variable is correctly forwarded */
		$response = $this->get("ControllerTest_ContainerController/subcontroller/subaction");
		$this->assertEquals('subaction', $response->getBody());

		$request = new SS_HTTPRequest(
			'GET',
			'ControllerTest_ContainerController/subcontroller/substring/subvieweraction'
		);
		/* Shift to emulate the director selecting the controller */
		$request->shift();
		/* Handle the request to create conditions where improperly passing the action to the viewer might fail */
		$controller = new ControllerTest_ContainerController();
		try {
			$controller->handleRequest($request, DataModel::inst());
		}
		catch(ControllerTest_SubController_Exception $e) {
			$this->fail($e->getMessage());
		}
	}
}

/**
 * Simple controller for testing
 */
class ControllerTest_Controller extends Controller implements TestOnly {

	public $Content = "default content";

	private static $allowed_actions = array(
		'methodaction',
		'stringaction',
		'redirectbacktest',
		'templateaction'
	);

	public function methodaction() {
		return array(
			"Content" => "methodaction content"
		);
	}

	public function stringaction() {
		return "stringaction was called.";
	}

	public function redirectbacktest() {
		return $this->redirectBack();
	}
}

class ControllerTest_UnsecuredController extends Controller implements TestOnly {

	// Not defined, allow access to all
	// static $allowed_actions = array();

	// Granted for all
	public function method1() {}

	// Granted for all
	public function method2() {}
	}

class ControllerTest_AccessBaseController extends Controller implements TestOnly {

	private static $allowed_actions = array();

	// Denied for all
	public function method1() {}

	// Denied for all
	public function method2() {}
	}

class ControllerTest_AccessSecuredController extends ControllerTest_AccessBaseController implements TestOnly {

	private static $allowed_actions = array(
		"method1", // denied because only defined in parent
		"method2" => true, // granted because its redefined
		"adminonly" => "ADMIN",
		'templateaction' => 'ADMIN'
	);

	public function method2() {}

	public function adminonly() {}

	protected function protectedmethod()  {}

}

class ControllerTest_AccessWildcardSecuredController extends ControllerTest_AccessBaseController implements TestOnly {

	private static $allowed_actions = array(
		"*" => "ADMIN", // should throw exception
	);

	}

class ControllerTest_IndexSecuredController extends ControllerTest_AccessBaseController implements TestOnly {

	private static $allowed_actions = array(
		"index" => "ADMIN",
	);

	}

class ControllerTest_AccessBaseControllerExtension extends Extension implements TestOnly {

	private static $allowed_actions = array(
		"extensionmethod1" => true, // granted because defined on this class
		"method1" => true, // ignored because method not defined on this class
		"method2" => true, // ignored because method not defined on this class
		"protectedextensionmethod" => true, // ignored because method is protected
	);

	// Allowed for all
	public function extensionmethod1() {}

	// Denied for all, not defined
	public function extensionmethod2() {}

	// Denied because its protected
	protected function protectedextensionmethod() {}

	public function internalextensionmethod() {}

	}

class ControllerTest_HasAction extends Controller {

	private static $allowed_actions = array (
		'allowed_action',
		//'other_action' => 'lowercase_permission'
	);

	protected $templates = array (
		'template_action' => 'template'
	);

}

class ControllerTest_HasAction_Unsecured extends ControllerTest_HasAction implements TestOnly {

	public function defined_action() {  }

}

class ControllerTest_ContainerController extends Controller implements TestOnly {

	private static $allowed_actions = array(
		'subcontroller',
	);

	public function subcontroller() {
		return new ControllerTest_SubController();
	}

}

class ControllerTest_SubController extends Controller implements TestOnly {

	private static $allowed_actions = array(
		'subaction',
		'subvieweraction',
	);

	private static $url_handlers = array(
		'substring/subvieweraction' => 'subvieweraction',
	);

	public function subaction() {
		return $this->getAction();
	}

	/* This is messy, but Controller->handleRequest is a hard to test method which warrants such measures... */
	public function getViewer($action) {
		if(empty($action)) {
			throw new ControllerTest_SubController_Exception("Null action passed, getViewer will break");
		}
		return parent::getViewer($action);
	}

	public function subvieweraction() {
		return $this->customise(array(
			'Thoughts' => 'Hope this works',
		));
	}

}

class ControllerTest_SubController_Exception extends Exception {

}
