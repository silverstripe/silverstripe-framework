<?php

class ControllerTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/ControllerTest.yml';
	
	protected $autoFollowRedirection = false;
	
	function testDefaultAction() {
		/* For a controller with a template, the default action will simple run that template. */
		$response = $this->get("ControllerTest_Controller/");
		$this->assertRegExp("/This is the main template. Content is 'default content'/", $response->getBody());
	}
	
	function testMethodActions() {
		/* The Action can refer to a method that is called on the object.  If a method returns an array, then it will be 
		used to customise the template data */
		$response = $this->get("ControllerTest_Controller/methodaction");
		$this->assertRegExp("/This is the main template. Content is 'methodaction content'./", $response->getBody());
		
		/* If the method just returns a string, then that will be used as the response */
		$response = $this->get("ControllerTest_Controller/stringaction");
		$this->assertRegExp("/stringaction was called./", $response->getBody());
	}
	
	function testTemplateActions() {
		/* If there is no method, it can be used to point to an alternative template. */
		$response = $this->get("ControllerTest_Controller/templateaction");
		$this->assertRegExp("/This is the template for templateaction. Content is 'default content'./", $response->getBody());
	}
	
	public function testUndefinedActions() {
		$response = Director::test('ControllerTest_AccessUnsecuredSubController/undefinedaction');
		$this->assertEquals(404, $response->getStatusCode(), 'Undefined actions return a not found response.');
	}
	
	function testAllowedActions() {
		$adminUser = $this->objFromFixture('Member', 'admin');
		
		$response = $this->get("ControllerTest_AccessBaseController/unsecuredaction");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on action without $allowed_actions on defining controller'
		);

		$response = $this->get("ControllerTest_AccessBaseController/onlysecuredinsubclassaction");
		$this->assertEquals(200, $response->getStatusCode(),
			'Access granted on action without $allowed_actions on defining controller, ' .
			'even when action is secured in subclasses'
		);
		
		$response = $this->get("ControllerTest_AccessSecuredController/onlysecuredinsubclassaction");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'even if action is unsecured on parent class'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/adminonly");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'when action is not defined on any parent classes'
		);

		$response = $this->get("ControllerTest_AccessSecuredController/aDmiNOnlY");
		$this->assertEquals(403, $response->getStatusCode(),
			'Access denied on action with $allowed_actions on defining controller, ' .
			'regardless of capitalization'
		);
		
		// TODO Change this API
		$response = $this->get('ControllerTest_AccessUnsecuredSubController/unsecuredaction');
		$this->assertEquals(200, $response->getStatusCode(), 
			"Controller without a specified allowed_actions allows its own actions through"
		);

		$response = $this->get('ControllerTest_AccessUnsecuredSubController/adminonly');
		$this->assertEquals(403, $response->getStatusCode(), 
			"Controller without a specified allowed_actions still disallows actions defined on parents"
		);
		
		$response = $this->get("ControllerTest_AccessAsteriskSecuredController/index");
		$this->assertEquals(403, $response->getStatusCode(),
			"Actions can be globally disallowed by using asterisk (*) for index method"
		);
		
		$response = $this->get("ControllerTest_AccessAsteriskSecuredController/onlysecuredinsubclassaction");
		$this->assertEquals(404, $response->getStatusCode(),
			"Actions can be globally disallowed by using asterisk (*) instead of a method name, " .
			"in which case they'll be marked as 'not found'"
		);
		
		$response = $this->get("ControllerTest_AccessAsteriskSecuredController/unsecuredaction");
		$this->assertEquals(200, $response->getStatusCode(),
			"Actions can be overridden to be allowed if globally disallowed by using asterisk (*)"
		);
		
		$this->session()->inst_set('loggedInAs', $adminUser->ID);
		$response = $this->get("ControllerTest_AccessSecuredController/adminonly");
		$this->assertEquals(
			200, 
			$response->getStatusCode(), 
			"Permission codes are respected when set in \$allowed_actions"
		);

		$response = $this->get("ControllerTest_AccessUnsecuredSubController/adminonly");
		$this->assertEquals(200, $response->getStatusCode(),
			"Actions can be globally disallowed by using asterisk (*) instead of a method name"
		);

		$this->session()->inst_set('loggedInAs', null);
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
		
		$this->assertEquals (
			'admin/action', Controller::join_links('admin/', '/', '/action'), 'Test that multiple slashes are trimmed.'
		);
		
		$this->assertEquals('/admin/action', Controller::join_links('/admin', 'action'));

		/* One fragment identifier is handled as you would expect */
		$this->assertEquals("my-page?arg=var#subsection", Controller::join_links("my-page#subsection", "?arg=var"));

		/* If there are multiple, it takes the last one */
		$this->assertEquals("my-page?arg=var#second-section", Controller::join_links("my-page#subsection", "?arg=var", "#second-section"));
	}
	
	/**
	 * @covers Controller::hasAction
	 */
	public function testHasAction() {
		$controller = new ControllerTest_HasAction();
		
		$this->assertFalse($controller->hasAction('1'), 'Numeric actions do not slip through.');
		//$this->assertFalse($controller->hasAction('lowercase_permission'), 'Lowercase permission does not slip through.');
		$this->assertFalse($controller->hasAction('undefined'), 'undefined actions do not exist');
		$this->assertTrue($controller->hasAction('allowed_action'), 'allowed actions are recognised');
		$this->assertTrue($controller->hasAction('template_action'), 'action-specific templates are recognised');
		
		$unsecured = new ControllerTest_HasAction_Unsecured();
		
		$this->assertTrue (
			$unsecured->hasAction('defined_action'),
			'Without an allowed_actions, any defined methods are recognised as actions'
		);
	}

	/* Controller::BaseURL no longer exists, but was just a direct call to Director::BaseURL, so not sure what this code was supposed to test
	public function testBaseURL() {
		Director::setBaseURL('/baseurl/');
		$this->assertEquals(Controller::BaseURL(), Director::BaseURL());
	}
	*/

	function testRedirectBackByReferer() {
		$internalRelativeUrl = '/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest', null, array('Referer' => $internalRelativeUrl));
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals($internalRelativeUrl, $response->getHeader('Location'),
			"Redirects on internal relative URLs"
		);

		$internalAbsoluteUrl = Director::absoluteBaseURL() . '/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest', null, array('Referer' => $internalAbsoluteUrl));
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'),
			"Redirects on internal absolute URLs"
		);

		$externalAbsoluteUrl = 'http://myhost.com/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest', null, array('Referer' => $externalAbsoluteUrl));
		$this->assertEquals(200, $response->getStatusCode(),
			"Doesn't redirect on external URLs"
		);
	}

	function testRedirectBackByBackUrl() {
		$internalRelativeUrl = '/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest?_REDIRECT_BACK_URL=' . urlencode($internalRelativeUrl));
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals($internalRelativeUrl, $response->getHeader('Location'),
			"Redirects on internal relative URLs"
		);

		$internalAbsoluteUrl = Director::absoluteBaseURL() . '/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest?_REDIRECT_BACK_URL=' . urlencode($internalAbsoluteUrl));
		$this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'));
		$this->assertEquals(302, $response->getStatusCode(),
			"Redirects on internal absolute URLs"
		);

		$externalAbsoluteUrl = 'http://myhost.com/some-url';
		$response = $this->get('ControllerTest_Controller/redirectbacktest?_REDIRECT_BACK_URL=' . urlencode($externalAbsoluteUrl));
		$this->assertEquals(200, $response->getStatusCode(),
			"Doesn't redirect on external URLs"
		);
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

	function redirectbacktest() {
		return $this->redirectBack();
	}
}

/**
 * Allowed actions flattened:
 * - unsecuredaction: *
 * - onlysecuredinsubclassaction: *
 * - unsecuredinparentclassaction: *
 * - unsecuredinsubclassaction: *
 */
class ControllerTest_AccessBaseController extends Controller implements TestOnly {
	// Accessible by all
	public function unsecuredaction() {
		return 'unsecuredaction was called.';
	}

	// Accessible by all
	public function onlysecuredinsubclassaction() {
		return 'onlysecuredinsubclass was called.';
	}

	// Accessible by all
	public function unsecuredinparentclassaction() {
		return 'unsecuredinparentclassaction was called.';
	}

	// Accessible by all
	public function unsecuredinsubclassaction() {
		return 'unsecuredinsubclass was called.';
	}
}

/**
 * Allowed actions flattened:
 * - unsecuredaction: *
 * - onlysecuredinsubclassaction: ADMIN (parent: *)
 * - unsecuredinparentclassaction: *
 * - unsecuredinsubclassaction: (none) (parent: *)
 * - adminonly: ADMIN
 */
class ControllerTest_AccessSecuredController extends ControllerTest_AccessBaseController implements TestOnly {
	
	static $allowed_actions = array(
		"onlysecuredinsubclassaction" => 'ADMIN',
		"adminonly" => "ADMIN",
	);
		
	// Accessible by ADMIN only
	public function onlysecuredinsubclassaction() {
		return 'onlysecuredinsubclass was called.';
	}

	// Not accessible, since overloaded but not mentioned in $allowed_actions
	public function unsecuredinsubclassaction() {
		return 'unsecuredinsubclass was called.';
	}

	// Accessible by all, since only defined in parent class
	//public function unsecuredinparentclassaction() {

	// Accessible by ADMIN only
	public function adminonly() {
		return "You must be an admin!";
	}
}

/**
 * Allowed actions flattened:
 * - unsecuredaction: *
 * - onlysecuredinsubclassaction: ADMIN (parent: *)
 * - unsecuredinparentclassaction: ADMIN (parent: *)
 * - unsecuredinsubclassaction: (none) (parent: *)
 * - adminonly: ADMIN (parent: ADMIN)
 */
class ControllerTest_AccessAsteriskSecuredController extends ControllerTest_AccessBaseController implements TestOnly {
	
	static $allowed_actions = array(
		"*" => "ADMIN",
		'unsecuredaction' => true,
	);
	
	// Accessible by ADMIN only
	public function adminonly() {
		return "You must be an admin!";
	}
	
	// Accessible by all
	public function unsecuredaction() {
		return "Allowed for everybody";
	}
}

/**
 * Allowed actions flattened:
 * - unsecuredaction: *
 * - onlysecuredinsubclassaction: ADMIN (parent: *)
 * - unsecuredinparentclassaction: *
 * - unsecuredinsubclassaction: (none) (parent: *)
 * - adminonly: ADMIN
 */
class ControllerTest_AccessUnsecuredSubController extends ControllerTest_AccessSecuredController implements TestOnly {
	
	// No $allowed_actions defined here

	// Accessible by ADMIN only, defined in parent class
	public function onlysecuredinsubclassaction() {
		return 'onlysecuredinsubclass was called.';
	}
}

class ControllerTest_HasAction extends Controller {
	
	public static $allowed_actions = array (
		'allowed_action',
		//'other_action' => 'lowercase_permission'
	);
	
	protected $templates = array (
		'template_action' => 'template'
	);
	
}

class ControllerTest_HasAction_Unsecured extends ControllerTest_HasAction {
	
	public function defined_action() {  }
	
}
