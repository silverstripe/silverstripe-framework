<?php

namespace SilverStripe\Control\Tests;

use InvalidArgumentException;
use PHPUnit_Framework_Error;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Tests\ControllerTest\AccessBaseController;
use SilverStripe\Control\Tests\ControllerTest\AccessSecuredController;
use SilverStripe\Control\Tests\ControllerTest\AccessWildcardSecuredController;
use SilverStripe\Control\Tests\ControllerTest\ContainerController;
use SilverStripe\Control\Tests\ControllerTest\HasAction;
use SilverStripe\Control\Tests\ControllerTest\HasAction_Unsecured;
use SilverStripe\Control\Tests\ControllerTest\IndexSecuredController;
use SilverStripe\Control\Tests\ControllerTest\SubController;
use SilverStripe\Control\Tests\ControllerTest\TestController;
use SilverStripe\Control\Tests\ControllerTest\UnsecuredController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DataModel;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;

class ControllerTest extends FunctionalTest
{

    protected static $fixture_file = 'ControllerTest.yml';

    protected $autoFollowRedirection = false;

    protected $depSettings = null;

    protected static $extra_controllers = [
        AccessBaseController::class,
        AccessSecuredController::class,
        AccessWildcardSecuredController::class,
        ContainerController::class,
        HasAction::class,
        HasAction_Unsecured::class,
        IndexSecuredController::class,
        SubController::class,
        TestController::class,
        UnsecuredController::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        Director::config()->update('alternate_base_url', '/');
        $this->depSettings = Deprecation::dump_settings();

        // Add test theme
        $themeDir = substr(__DIR__, strlen(FRAMEWORK_DIR)) . '/ControllerTest/';
        $themes = [
            "silverstripe/framework:{$themeDir}",
            SSViewer::DEFAULT_THEME
        ];
        SSViewer::set_themes($themes);
    }

    protected function tearDown()
    {
        Deprecation::restore_settings($this->depSettings);
        parent::tearDown();
    }

    public function testDefaultAction()
    {
        /* For a controller with a template, the default action will simple run that template. */
        $response = $this->get("TestController/");
        $this->assertRegExp("/This is the main template. Content is 'default content'/", $response->getBody());
    }

    public function testMethodActions()
    {
        /* The Action can refer to a method that is called on the object.  If a method returns an array, then it
        * will be used to customise the template data */
        $response = $this->get("TestController/methodaction");
        $this->assertRegExp("/This is the main template. Content is 'methodaction content'./", $response->getBody());

        /* If the method just returns a string, then that will be used as the response */
        $response = $this->get("TestController/stringaction");
        $this->assertRegExp("/stringaction was called./", $response->getBody());
    }

    public function testTemplateActions()
    {
        /* If there is no method, it can be used to point to an alternative template. */
        $response = $this->get("TestController/templateaction");
        $this->assertRegExp(
            "/This is the template for templateaction. Content is 'default content'./",
            $response->getBody()
        );
    }

    public function testUndefinedActions()
    {
        $response = $this->get('IndexSecuredController/undefinedaction');
        $this->assertInstanceOf('SilverStripe\\Control\\HTTPResponse', $response);
        $this->assertEquals(404, $response->getStatusCode(), 'Undefined actions return a not found response.');
    }

    public function testAllowedActions()
    {
        $adminUser = $this->objFromFixture(Member::class, 'admin');

        $response = $this->get("UnsecuredController/");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index action without $allowed_actions on defining controller, ' .
            'when called without an action in the URL'
        );

        $response = $this->get("UnsecuredController/index");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access denied on index action without $allowed_actions on defining controller, ' .
            'when called with an action in the URL'
        );

        $response = $this->get("UnsecuredController/method1");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action without $allowed_actions on defining controller, ' .
            'when called without an action in the URL'
        );

        $response = $this->get("AccessBaseController/");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index with empty $allowed_actions on defining controller, ' .
            'when called without an action in the URL'
        );

        $response = $this->get("AccessBaseController/index");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index with empty $allowed_actions on defining controller, ' .
            'when called with an action in the URL'
        );

        $response = $this->get("AccessBaseController/method1");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with empty $allowed_actions on defining controller'
        );

        $response = $this->get("AccessBaseController/method2");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with empty $allowed_actions on defining controller, ' .
            'even when action is allowed in subclasses (allowed_actions don\'t inherit)'
        );

        $response = $this->get("AccessSecuredController/");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index with non-empty $allowed_actions on defining controller, ' .
            'even when index isn\'t specifically mentioned in there'
        );

        $response = $this->get("AccessSecuredController/method1");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action which is only defined in parent controller, ' .
            'even when action is allowed in currently called class (allowed_actions don\'t inherit)'
        );

        $response = $this->get("AccessSecuredController/method2");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on action originally defined with empty $allowed_actions on parent controller, ' .
            'because it has been redefined in the subclass'
        );

        $response = $this->get("AccessSecuredController/templateaction");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' .
            'if action is not a method but rather a template discovered by naming convention'
        );

        $response = $this->get("AccessSecuredController/templateaction");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' .
            'if action is not a method but rather a template discovered by naming convention'
        );

        Security::setCurrentUser($adminUser);
        $response = $this->get("AccessSecuredController/templateaction");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted for logged in admin on action with $allowed_actions on defining controller, ' .
            'if action is not a method but rather a template discovered by naming convention'
        );

        Security::setCurrentUser(null);
        $response = $this->get("AccessSecuredController/adminonly");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' .
            'when restricted by unmatched permission code'
        );

        $response = $this->get("AccessSecuredController/aDmiNOnlY");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' .
            'regardless of capitalization'
        );

        $response = $this->get('AccessSecuredController/protectedmethod');
        $this->assertEquals(
            404,
            $response->getStatusCode(),
            "Access denied to protected method even if its listed in allowed_actions"
        );

        Security::setCurrentUser($adminUser);
        $response = $this->get("AccessSecuredController/adminonly");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Permission codes are respected when set in \$allowed_actions"
        );

        Security::setCurrentUser(null);
        $response = $this->get('AccessBaseController/extensionmethod1');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Access granted to method defined in allowed_actions on extension, " .
            "where method is also defined on extension"
        );

        $response = $this->get('AccessSecuredController/extensionmethod1');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Access granted to method defined in allowed_actions on extension, " .
            "where method is also defined on extension, even when called in a subclass"
        );

        $response = $this->get('AccessBaseController/extensionmethod2');
        $this->assertEquals(
            404,
            $response->getStatusCode(),
            "Access denied to method not defined in allowed_actions on extension, " .
            "where method is also defined on extension"
        );

        $response = $this->get('IndexSecuredController/');
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            "Access denied when index action is limited through allowed_actions, " .
            "and doesn't satisfy checks, and action is empty"
        );

        $response = $this->get('IndexSecuredController/index');
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            "Access denied when index action is limited through allowed_actions, " .
            "and doesn't satisfy checks"
        );

        Security::setCurrentUser($adminUser);
        $response = $this->get('IndexSecuredController/');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Access granted when index action is limited through allowed_actions, " .
            "and does satisfy checks"
        );
        Security::setCurrentUser(null);
    }

    public function testWildcardAllowedActions()
    {
        $this->setExpectedException(InvalidArgumentException::class, "Invalid allowed_action '*'");
        $this->get('AccessWildcardSecuredController');
    }

    /**
     * Test Controller::join_links()
     */
    public function testJoinLinks()
    {
        /* Controller::join_links() will reliably join two URL-segments together so that they will be
        * appropriately parsed by the URL parser */
        $this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm", "MyForm"));
        $this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm/", "MyForm"));

        /* It will also handle appropriate combination of querystring variables */
        $this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/?flush=1", "MyForm"));
        $this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/", "MyForm?flush=1"));
        $this->assertEquals(
            "admin/crm/MyForm?field=1&other=1",
            Controller::join_links("admin/crm/?field=1", "MyForm?other=1")
        );

        /* It can handle arbitrary numbers of components, and will ignore empty ones */
        $this->assertEquals("admin/crm/MyForm/", Controller::join_links("admin/", "crm", "", "MyForm/"));
        $this->assertEquals(
            "admin/crm/MyForm/?a=1&b=2",
            Controller::join_links("admin/?a=1", "crm", "", "MyForm/?b=2")
        );

        /* It can also be used to attach additional get variables to a link */
        $this->assertEquals("admin/crm?flush=1", Controller::join_links("admin/crm", "?flush=1"));
        $this->assertEquals("admin/crm?existing=1&flush=1", Controller::join_links("admin/crm?existing=1", "?flush=1"));
        $this->assertEquals(
            "admin/crm/MyForm?a=1&b=2&c=3",
            Controller::join_links("?a=1", "admin/crm", "?b=2", "MyForm?c=3")
        );

        // And duplicates are handled nicely
        $this->assertEquals(
            "admin/crm?foo=2&bar=3&baz=1",
            Controller::join_links("admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );

        $this->assertEquals(
            'admin/action',
            Controller::join_links('admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );

        $this->assertEquals('/admin/action', Controller::join_links('/admin', 'action'));

        /* One fragment identifier is handled as you would expect */
        $this->assertEquals("my-page?arg=var#subsection", Controller::join_links("my-page#subsection", "?arg=var"));

        /* If there are multiple, it takes the last one */
        $this->assertEquals(
            "my-page?arg=var#second-section",
            Controller::join_links("my-page#subsection", "?arg=var", "#second-section")
        );

        /* Does type-safe checks for zero value */
        $this->assertEquals("my-page/0", Controller::join_links("my-page", 0));

        // Test array args
        $this->assertEquals(
            "admin/crm/MyForm?a=1&b=2&c=3",
            Controller::join_links(["?a=1", "admin/crm", "?b=2", "MyForm?c=3"])
        );
    }

    public function testLink()
    {
        $controller = new HasAction();
        $this->assertEquals('HasAction/', $controller->Link());
        $this->assertEquals('HasAction/', $controller->Link(null));
        $this->assertEquals('HasAction/', $controller->Link(false));
        $this->assertEquals('HasAction/allowed-action/', $controller->Link('allowed-action'));
    }

    /**
     * @covers \SilverStripe\Control\Controller::hasAction
     */
    public function testHasAction()
    {
        $controller = new HasAction();
        $unsecuredController = new HasAction_Unsecured();
        $securedController = new AccessSecuredController();

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

        $this->assertTrue(
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
    Config::modify()->merge('Director', 'alternate_base_url', '/baseurl/');
    $this->assertEquals(Controller::BaseURL(), Director::BaseURL());
    }
    */

    public function testRedirectBackByReferer()
    {
        $internalRelativeUrl = Controller::join_links(Director::baseURL(), '/some-url');
        $internalAbsoluteUrl = Controller::join_links(Director::absoluteBaseURL(), '/some-url');

        $response = $this->get(
            'TestController/redirectbacktest',
            null,
            array('Referer' => $internalRelativeUrl)
        );
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            $internalAbsoluteUrl,
            $response->getHeader('Location'),
            "Redirects on internal relative URLs"
        );

        $response = $this->get(
            'TestController/redirectbacktest',
            null,
            array('Referer' => $internalAbsoluteUrl)
        );
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            $internalAbsoluteUrl,
            $response->getHeader('Location'),
            "Redirects on internal absolute URLs"
        );

        $externalAbsoluteUrl = 'http://myhost.com/some-url';
        $response = $this->get(
            'TestController/redirectbacktest',
            null,
            array('Referer' => $externalAbsoluteUrl)
        );
        $this->assertEquals(
            Director::absoluteBaseURL(),
            $response->getHeader('Location'),
            "Redirects back to home page on external url"
        );
    }

    public function testRedirectBackByBackUrl()
    {
        $internalRelativeUrl = Controller::join_links(Director::baseURL(), '/some-url');
        $internalAbsoluteUrl = Controller::join_links(Director::absoluteBaseURL(), '/some-url');

        $response = $this->get('TestController/redirectbacktest?BackURL=' . urlencode($internalRelativeUrl));
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            $internalAbsoluteUrl,
            $response->getHeader('Location'),
            "Redirects on internal relative URLs"
        );

        // BackURL is internal link
        $internalAbsoluteUrl = Director::absoluteBaseURL() . '/some-url';
        $link = 'TestController/redirectbacktest?BackURL=' . urlencode($internalAbsoluteUrl);
        $response = $this->get($link);
        $this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'));
        $this->assertEquals(
            302,
            $response->getStatusCode(),
            "Redirects on internal absolute URLs"
        );

        // Note that this test is affected by the prior ->get()
        $externalAbsoluteUrl = 'http://myhost.com/some-url';
        $response = $this->get('TestController/redirectbacktest?BackURL=' . urlencode($externalAbsoluteUrl));
        $this->assertEquals(
            Director::absoluteURL($link),
            $response->getHeader('Location'),
            "If BackURL Is external link, fall back to last url (Referer)"
        );
    }

    public function testSubActions()
    {
        /* If a controller action returns another controller, ensure that the $action variable is correctly forwarded */
        $response = $this->get("ContainerController/subcontroller/subaction");
        $this->assertEquals('subaction', $response->getBody());

        $request = new HTTPRequest(
            'GET',
            'ContainerController/subcontroller/substring/subvieweraction'
        );
        /* Shift to emulate the director selecting the controller */
        $request->shift();
        /* Handle the request to create conditions where improperly passing the action to the viewer might fail */
        $controller = new ControllerTest\ContainerController();
        try {
            $controller->handleRequest($request, DataModel::inst());
        } catch (ControllerTest\SubController_Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
