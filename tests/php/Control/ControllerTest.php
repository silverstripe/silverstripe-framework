<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
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
use SilverStripe\Control\Tests\RequestHandlingTest\HTTPMethodTestController;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
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
        HTTPMethodTestController::class,
        IndexSecuredController::class,
        SubController::class,
        TestController::class,
        UnsecuredController::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Director::config()->set('alternate_base_url', '/');

        // Add test theme
        $themeDir = substr(__DIR__, strlen(FRAMEWORK_DIR)) . '/ControllerTest/';
        $themes = [
            "silverstripe/framework:{$themeDir}",
            SSViewer::DEFAULT_THEME,
        ];
        SSViewer::set_themes($themes);
    }

    public function testDefaultAction()
    {
        /* For a controller with a template, the default action will simple run that template. */
        $response = $this->get("TestController");
        $this->assertStringContainsString("This is the main template. Content is 'default content'", $response->getBody());
    }

    public function testMethodActions()
    {
        /* The Action can refer to a method that is called on the object.  If a method returns an array, then it
        * will be used to customise the template data */
        $response = $this->get("TestController/methodaction");
        $this->assertStringContainsString("This is the main template. Content is 'methodaction content'.", $response->getBody());

        /* If the method just returns a string, then that will be used as the response */
        $response = $this->get("TestController/stringaction");
        $this->assertStringContainsString("stringaction was called.", $response->getBody());
    }

    public function testTemplateActions()
    {
        /* If there is no method, it can be used to point to an alternative template. */
        $response = $this->get("TestController/templateaction");
        $this->assertStringContainsString(
            "This is the template for templateaction. Content is 'default content'.",
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
        $response = $this->get("UnsecuredController");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index action without $allowed_actions on defining controller, ' . 'when called without an action in the URL'
        );

        $response = $this->get("UnsecuredController/index");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access denied on index action without $allowed_actions on defining controller, ' . 'when called with an action in the URL'
        );

        $response = $this->get("UnsecuredController/method1");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action without $allowed_actions on defining controller, ' . 'when called without an action in the URL'
        );

        $response = $this->get("AccessBaseController");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index with empty $allowed_actions on defining controller, ' . 'when called without an action in the URL'
        );

        $response = $this->get("AccessBaseController/index");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index with empty $allowed_actions on defining controller, ' . 'when called with an action in the URL'
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
            'Access denied on action with empty $allowed_actions on defining controller, ' . 'even when action is allowed in subclasses (allowed_actions don\'t inherit)'
        );

        $response = $this->get("AccessSecuredController");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on index with non-empty $allowed_actions on defining controller, ' . 'even when index isn\'t specifically mentioned in there'
        );

        $response = $this->get("AccessSecuredController/method1");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action which is only defined in parent controller, ' . 'even when action is allowed in currently called class (allowed_actions don\'t inherit)'
        );

        $response = $this->get("AccessSecuredController/method2");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted on action originally defined with empty $allowed_actions on parent controller, ' . 'because it has been redefined in the subclass'
        );

        $response = $this->get("AccessSecuredController/templateaction");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' . 'if action is not a method but rather a template discovered by naming convention'
        );

        $response = $this->get("AccessSecuredController/templateaction");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' . 'if action is not a method but rather a template discovered by naming convention'
        );

        $this->logInAs('admin');
        $response = $this->get("AccessSecuredController/templateaction");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Access granted for logged in admin on action with $allowed_actions on defining controller, ' . 'if action is not a method but rather a template discovered by naming convention'
        );
        $this->logOut();

        $response = $this->get("AccessSecuredController/adminonly");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' . 'when restricted by unmatched permission code'
        );

        $response = $this->get("AccessSecuredController/aDmiNOnlY");
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            'Access denied on action with $allowed_actions on defining controller, ' . 'regardless of capitalization'
        );

        $response = $this->get('AccessSecuredController/protectedmethod');
        $this->assertEquals(
            404,
            $response->getStatusCode(),
            "Access denied to protected method even if its listed in allowed_actions"
        );

        $this->logInAs('admin');
        $response = $this->get("AccessSecuredController/adminonly");
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Permission codes are respected when set in \$allowed_actions"
        );
        $this->logOut();

        $response = $this->get('AccessBaseController/extensionmethod1');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Access granted to method defined in allowed_actions on extension, " . "where method is also defined on extension"
        );

        $response = $this->get('AccessSecuredController/extensionmethod1');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Access granted to method defined in allowed_actions on extension, " . "where method is also defined on extension, even when called in a subclass"
        );

        $response = $this->get('AccessBaseController/extensionmethod2');
        $this->assertEquals(
            404,
            $response->getStatusCode(),
            "Access denied to method not defined in allowed_actions on extension, " . "where method is also defined on extension"
        );

        $response = $this->get('IndexSecuredController');
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            "Access denied when index action is limited through allowed_actions, " . "and doesn't satisfy checks, and action is empty"
        );

        $response = $this->get('IndexSecuredController/index');
        $this->assertEquals(
            403,
            $response->getStatusCode(),
            "Access denied when index action is limited through allowed_actions, " . "and doesn't satisfy checks"
        );

        $this->logInAs('admin');
        $response = $this->get('IndexSecuredController');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            "Access granted when index action is limited through allowed_actions, " . "and does satisfy checks"
        );
        $this->logOut();
    }

    public function testWildcardAllowedActions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid allowed_action '*'");
        $this->get('AccessWildcardSecuredController');
    }

    /**
     * Test Controller::join_links()
     */
    public function testJoinLinks()
    {
        /* Controller::join_links() will reliably join two URL-segments together so that they will be
        * appropriately parsed by the URL parser */
        Director::config()->set('alternate_base_url', 'https://www.internal.com');
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm", "MyForm"));
        $this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/crm/", "MyForm"));
        $this->assertEquals("https://www.internal.com/admin/crm/MyForm", Controller::join_links("https://www.internal.com", "admin/crm/", "MyForm"));
        $this->assertEquals("https://www.external.com/admin/crm/MyForm", Controller::join_links("https://www.external.com", "admin/crm/", "MyForm"));
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals("admin/crm/MyForm/", Controller::join_links("admin/crm", "MyForm"));
        $this->assertEquals("admin/crm/MyForm/", Controller::join_links("admin/crm/", "MyForm"));
        $this->assertEquals("https://www.internal.com/admin/crm/MyForm/", Controller::join_links("https://www.internal.com", "admin/crm/", "MyForm"));
        $this->assertEquals("https://www.external.com/admin/crm/MyForm", Controller::join_links("https://www.external.com", "admin/crm/", "MyForm"));

        /* It will also handle appropriate combination of querystring variables */
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/?flush=1", "MyForm"));
        $this->assertEquals("admin/crm/MyForm?flush=1", Controller::join_links("admin/crm/", "MyForm?flush=1"));
        $this->assertEquals(
            "admin/crm/MyForm?field=1&other=1",
            Controller::join_links("admin/crm/?field=1", "MyForm?other=1")
        );
        $this->assertEquals("https://www.internal.com/admin/crm/MyForm?flush=1", Controller::join_links("https://www.internal.com", "admin/crm/", "MyForm?flush=1"));
        $this->assertEquals("https://www.external.com/admin/crm/MyForm?flush=1", Controller::join_links("https://www.external.com", "admin/crm/", "MyForm?flush=1"));
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals("admin/crm/MyForm/?flush=1", Controller::join_links("admin/crm/?flush=1", "MyForm"));
        $this->assertEquals("admin/crm/MyForm/?flush=1", Controller::join_links("admin/crm/", "MyForm?flush=1"));
        $this->assertEquals(
            "admin/crm/MyForm/?field=1&other=1",
            Controller::join_links("admin/crm/?field=1", "MyForm?other=1")
        );
        $this->assertEquals("https://www.internal.com/admin/crm/MyForm/?flush=1", Controller::join_links("https://www.internal.com", "admin/crm/", "MyForm?flush=1"));
        $this->assertEquals("https://www.external.com/admin/crm/MyForm?flush=1", Controller::join_links("https://www.external.com", "admin/crm/", "MyForm?flush=1"));

        /* It can handle arbitrary numbers of components, and will ignore empty ones */
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals("admin/crm/MyForm", Controller::join_links("admin/", "crm", "", "MyForm/"));
        $this->assertEquals(
            "admin/crm/MyForm?a=1&b=2",
            Controller::join_links("admin/?a=1", "crm", "", "MyForm/?b=2")
        );
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals("admin/crm/MyForm/", Controller::join_links("admin/", "crm", "", "MyForm/"));
        $this->assertEquals(
            "admin/crm/MyForm/?a=1&b=2",
            Controller::join_links("admin/?a=1", "crm", "", "MyForm/?b=2")
        );

        /* It can also be used to attach additional get variables to a link */
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals("admin/crm?flush=1", Controller::join_links("admin/crm", "?flush=1"));
        $this->assertEquals("admin/crm?existing=1&flush=1", Controller::join_links("admin/crm?existing=1", "?flush=1"));
        $this->assertEquals(
            "admin/crm/MyForm?a=1&b=2&c=3",
            Controller::join_links("?a=1", "admin/crm", "?b=2", "MyForm?c=3")
        );
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals("admin/crm/?flush=1", Controller::join_links("admin/crm", "?flush=1"));
        $this->assertEquals("admin/crm/?existing=1&flush=1", Controller::join_links("admin/crm?existing=1", "?flush=1"));
        $this->assertEquals(
            "admin/crm/MyForm/?a=1&b=2&c=3",
            Controller::join_links("?a=1", "admin/crm", "?b=2", "MyForm?c=3")
        );

        // And duplicates are handled nicely
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals(
            "admin/crm?foo=2&bar=3&baz=1",
            Controller::join_links("admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );
        $this->assertEquals(
            "https://www.internal.com/admin/crm?foo=2&bar=3&baz=1",
            Controller::join_links("https://www.internal.com", "admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );
        $this->assertEquals(
            "https://www.external.com/admin/crm?foo=2&bar=3&baz=1",
            Controller::join_links("https://www.external.com", "admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals(
            "admin/crm/?foo=2&bar=3&baz=1",
            Controller::join_links("admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );
        $this->assertEquals(
            "https://www.internal.com/admin/crm/?foo=2&bar=3&baz=1",
            Controller::join_links("https://www.internal.com", "admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );
        $this->assertEquals(
            "https://www.external.com/admin/crm?foo=2&bar=3&baz=1",
            Controller::join_links("https://www.external.com", "admin/crm?foo=1&bar=1&baz=1", "?foo=2&bar=3")
        );

        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals(
            'admin/action',
            Controller::join_links('admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );
        $this->assertEquals('/admin/action', Controller::join_links('/admin', 'action'));
        $this->assertEquals(
            'https://www.internal.com/admin/action',
            Controller::join_links('https://www.internal.com', '/', '/admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );
        $this->assertEquals(
            'https://www.external.com/admin/action',
            Controller::join_links('https://www.external.com', '/', '/admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals(
            'admin/action/',
            Controller::join_links('admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );
        $this->assertEquals('/admin/action/', Controller::join_links('/admin', 'action'));
        $this->assertEquals(
            'https://www.internal.com/admin/action/',
            Controller::join_links('https://www.internal.com', '/', '/admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );
        $this->assertEquals(
            'https://www.external.com/admin/action',
            Controller::join_links('https://www.external.com', '/', '/admin/', '/', '/action'),
            'Test that multiple slashes are trimmed.'
        );

        /* One fragment identifier is handled as you would expect */
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals("my-page?arg=var#subsection", Controller::join_links("my-page#subsection", "?arg=var"));
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals("my-page/?arg=var#subsection", Controller::join_links("my-page#subsection", "?arg=var"));

        /* If there are multiple, it takes the last one */
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals(
            "my-page?arg=var#second-section",
            Controller::join_links("my-page#subsection", "?arg=var", "#second-section")
        );
        $this->assertEquals(
            "https://www.internal.com/my-page?arg=var#second-section",
            Controller::join_links("https://www.internal.com", "my-page#subsection", "?arg=var", "#second-section")
        );
        $this->assertEquals(
            "https://www.external.com/my-page?arg=var#second-section",
            Controller::join_links("https://www.external.com", "my-page#subsection", "?arg=var", "#second-section")
        );
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals(
            "my-page/?arg=var#second-section",
            Controller::join_links("my-page#subsection", "?arg=var", "#second-section")
        );
        $this->assertEquals(
            "https://www.internal.com/my-page/?arg=var#second-section",
            Controller::join_links("https://www.internal.com", "my-page#subsection", "?arg=var", "#second-section")
        );
        $this->assertEquals(
            "https://www.external.com/my-page?arg=var#second-section",
            Controller::join_links("https://www.external.com", "my-page#subsection", "?arg=var", "#second-section")
        );

        /* Does type-safe checks for zero value */
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals("my-page/0", Controller::join_links("my-page", 0));
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals("my-page/0/", Controller::join_links("my-page", 0));

        // Test array args
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals(
            "https://www.internal.com/admin/crm/MyForm?a=1&b=2&c=3",
            Controller::join_links(["https://www.internal.com", "?a=1", "admin/crm", "?b=2", "MyForm?c=3"])
        );
        $this->assertEquals(
            "https://www.external.com/admin/crm/MyForm?a=1&b=2&c=3",
            Controller::join_links(["https://www.external.com", "?a=1", "admin/crm", "?b=2", "MyForm?c=3"])
        );
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals(
            "https://www.internal.com/admin/crm/MyForm/?a=1&b=2&c=3",
            Controller::join_links(["https://www.internal.com", "?a=1", "admin/crm", "?b=2", "MyForm?c=3"])
        );
        $this->assertEquals(
            "https://www.external.com/admin/crm/MyForm?a=1&b=2&c=3",
            Controller::join_links(["https://www.external.com", "?a=1", "admin/crm", "?b=2", "MyForm?c=3"])
        );
    }

    public function provideNormaliseTrailingSlash(): array
    {
        // note 93.184.215.14 is the IP address for example.com
        return [
            // Correctly gives slash to a relative root path
            [
                'path' => '',
                'withSlash' => '/',
                'withoutSlash' => '/',
            ],
            [
                'path' => '/',
                'withSlash' => '/',
                'withoutSlash' => '/',
            ],
            // Correctly adds or removes trailing slash
            [
                'path' => 'some/path/',
                'withSlash' => 'some/path/',
                'withoutSlash' => 'some/path',
            ],
            // Retains leading slash, if there is one
            [
                'path' => '/some/path/',
                'withSlash' => '/some/path/',
                'withoutSlash' => '/some/path',
            ],
            // Treat absolute URLs pointing to the current site as relative
            [
                'path' => '<AbsoluteBaseUrl>/some/path/',
                'withSlash' => '<AbsoluteBaseUrl>/some/path/',
                'withoutSlash' => '<AbsoluteBaseUrl>/some/path',
            ],
            [
                'path' => '<AbsoluteBaseUrl>/',
                'withSlash' => '<AbsoluteBaseUrl>/',
                'withoutSlash' => '<AbsoluteBaseUrl>',
            ],
            [
                'path' => '<AbsoluteBaseUrl>',
                'withSlash' => '<AbsoluteBaseUrl>/',
                'withoutSlash' => '<AbsoluteBaseUrl>',
            ],
            // External links never get normalised
            [
                'path' => 'https://www.example.com/some/path',
                'withSlash' => 'https://www.example.com/some/path',
                'withoutSlash' => 'https://www.example.com/some/path',
            ],
            [
                'path' => 'https://www.example.com/some/path/',
                'withSlash' => 'https://www.example.com/some/path/',
                'withoutSlash' => 'https://www.example.com/some/path/',
            ],
            [
                'path' => 'https://www.example.com',
                'withSlash' => 'https://www.example.com',
                'withoutSlash' => 'https://www.example.com',
            ],
            [
                'path' => 'https://www.example.com/',
                'withSlash' => 'https://www.example.com/',
                'withoutSlash' => 'https://www.example.com/',
            ],
            [
                'path' => '//www.example.com/some/path',
                'withSlash' => '//www.example.com/some/path',
                'withoutSlash' => '//www.example.com/some/path',
            ],
            [
                'path' => '//www.example.com/some/path/',
                'withSlash' => '//www.example.com/some/path/',
                'withoutSlash' => '//www.example.com/some/path/',
            ],
            [
                'path' => '//www.example.com',
                'withSlash' => '//www.example.com',
                'withoutSlash' => '//www.example.com',
            ],
            [
                'path' => '//www.example.com/',
                'withSlash' => '//www.example.com/',
                'withoutSlash' => '//www.example.com/',
            ],
            [
                'path' => 'https://93.184.215.14/some/path',
                'withSlash' => 'https://93.184.215.14/some/path',
                'withoutSlash' => 'https://93.184.215.14/some/path',
            ],
            [
                'path' => 'https://93.184.215.14/some/path/',
                'withSlash' => 'https://93.184.215.14/some/path/',
                'withoutSlash' => 'https://93.184.215.14/some/path/',
            ],
            // Links without a scheme with a path are treated as relative
            // Note: content authors should be specifying a scheme in these cases themselves
            [
                'path' => 'www.example.com/some/path',
                'withSlash' => 'www.example.com/some/path/',
                'withoutSlash' => 'www.example.com/some/path',
            ],
            [
                'path' => 'www.example.com/some/path/',
                'withSlash' => 'www.example.com/some/path/',
                'withoutSlash' => 'www.example.com/some/path',
            ],
            [
                'path' => '93.184.215.14/some/path',
                'withSlash' => '93.184.215.14/some/path/',
                'withoutSlash' => '93.184.215.14/some/path',
            ],
            [
                'path' => '93.184.215.14/some/path/',
                'withSlash' => '93.184.215.14/some/path/',
                'withoutSlash' => '93.184.215.14/some/path',
            ],
            // Links without a scheme or path are treated like files i.e. not altered
            // Note: content authors should be specifying a scheme in these cases themselves
            [
                'path' => 'www.example.com',
                'withSlash' => 'www.example.com',
                'withoutSlash' => 'www.example.com',
            ],
            [
                'path' => 'www.example.com/',
                'withSlash' => 'www.example.com/',
                'withoutSlash' => 'www.example.com/',
            ],
            [
                'path' => '93.184.215.14',
                'withSlash' => '93.184.215.14',
                'withoutSlash' => '93.184.215.14',
            ],
            [
                'path' => '93.184.215.14/',
                'withSlash' => '93.184.215.14/',
                'withoutSlash' => '93.184.215.14/',
            ],
            // Retains query string and anchor if present
            [
                'path' => 'some/path/?key=value&key2=value2',
                'withSlash' => 'some/path/?key=value&key2=value2',
                'withoutSlash' => 'some/path?key=value&key2=value2',
            ],
            [
                'path' => 'some/path/#some-id',
                'withSlash' => 'some/path/#some-id',
                'withoutSlash' => 'some/path#some-id',
            ],
            [
                'path' => 'some/path?key=value&key2=value2#some-id',
                'withSlash' => 'some/path/?key=value&key2=value2#some-id',
                'withoutSlash' => 'some/path?key=value&key2=value2#some-id',
            ],
            [
                'path' => 'some/path?key=value&key2=value2',
                'withSlash' => 'some/path/?key=value&key2=value2',
                'withoutSlash' => 'some/path?key=value&key2=value2',
            ],
            [
                'path' => 'some/path#some-id',
                'withSlash' => 'some/path/#some-id',
                'withoutSlash' => 'some/path#some-id',
            ],
            [
                'path' => 'some/path?key=value&key2=value2#some-id',
                'withSlash' => 'some/path/?key=value&key2=value2#some-id',
                'withoutSlash' => 'some/path?key=value&key2=value2#some-id',
            ],
            // Don't ever add a trailing slash to the end of a URL that looks like a file
            [
                'path' => 'https://www.example.com/some/file.txt',
                'withSlash' => 'https://www.example.com/some/file.txt',
                'withoutSlash' => 'https://www.example.com/some/file.txt',
            ],
            [
                'path' => '//www.example.com/some/file.txt',
                'withSlash' => '//www.example.com/some/file.txt',
                'withoutSlash' => '//www.example.com/some/file.txt',
            ],
            [
                'path' => 'www.example.com/some/file.txt',
                'withSlash' => 'www.example.com/some/file.txt',
                'withoutSlash' => 'www.example.com/some/file.txt',
            ],
            [
                'path' => '/some/file.txt',
                'withSlash' => '/some/file.txt',
                'withoutSlash' => '/some/file.txt',
            ],
            [
                'path' => 'some/file.txt',
                'withSlash' => 'some/file.txt',
                'withoutSlash' => 'some/file.txt',
            ],
            [
                'path' => 'file.txt',
                'withSlash' => 'file.txt',
                'withoutSlash' => 'file.txt',
            ],
            [
                'path' => 'some/file.txt?key=value&key2=value2#some-id',
                'withSlash' => 'some/file.txt?key=value&key2=value2#some-id',
                'withoutSlash' => 'some/file.txt?key=value&key2=value2#some-id',
            ],
        ];
    }

    /**
     * @dataProvider provideNormaliseTrailingSlash
     */
    public function testNormaliseTrailingSlash(string $path, string $withSlash, string $withoutSlash): void
    {
        $absBaseUrlNoSlash = rtrim(Director::absoluteBaseURL(), '/');
        $path = str_replace('<AbsoluteBaseUrl>', $absBaseUrlNoSlash, $path);
        $withSlash = str_replace('<AbsoluteBaseUrl>', $absBaseUrlNoSlash, $withSlash);
        $withoutSlash = str_replace('<AbsoluteBaseUrl>', $absBaseUrlNoSlash, $withoutSlash);
        Controller::config()->set('add_trailing_slash', true);
        $this->assertEquals($withSlash, Controller::normaliseTrailingSlash($path), 'With trailing slash test');
        Controller::config()->set('add_trailing_slash', false);
        $this->assertEquals($withoutSlash, Controller::normaliseTrailingSlash($path), 'Without trailing slash test');
    }

    public function testLink()
    {
        $controller = new HasAction();

        Controller::config()->set('add_trailing_slash', false);

        $this->assertEquals('HasAction', $controller->Link());
        $this->assertEquals('HasAction', $controller->Link(null));
        $this->assertEquals('HasAction', $controller->Link(false));
        $this->assertEquals('HasAction/allowed-action', $controller->Link('allowed-action'));

        Controller::config()->set('add_trailing_slash', true);

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
        //  $controller->hasAction('lowercase_permission'),
        //  'Lowercase permission does not slip through.'
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
            'Method is not visible when defined on an extension, part of allowed_actions, ' . 'but with protected visibility'
        );
    }

    public function testRedirectBackByReferer()
    {
        $internalRelativeUrl = Controller::join_links(Director::baseURL(), '/some-url');
        $internalAbsoluteUrl = Controller::join_links(Director::absoluteBaseURL(), '/some-url');

        $response = $this->get(
            'TestController/redirectbacktest',
            null,
            ['Referer' => $internalRelativeUrl]
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
            ['Referer' => $internalAbsoluteUrl]
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
            ['Referer' => $externalAbsoluteUrl]
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

        $response = $this->get('TestController/redirectbacktest?BackURL=' . urlencode($internalRelativeUrl ?? ''));
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            $internalAbsoluteUrl,
            $response->getHeader('Location'),
            "Redirects on internal relative URLs"
        );

        // BackURL is internal link
        $internalAbsoluteUrl = Controller::join_links(Director::absoluteBaseURL(), '/some-url');
        $link = 'TestController/redirectbacktest?BackURL=' . urlencode($internalAbsoluteUrl ?? '');
        $response = $this->get($link);
        $this->assertEquals($internalAbsoluteUrl, $response->getHeader('Location'));
        $this->assertEquals(
            302,
            $response->getStatusCode(),
            "Redirects on internal absolute URLs"
        );

        // Note that this test is affected by the prior ->get()
        $externalAbsoluteUrl = 'http://myhost.com/some-url';
        $response = $this->get('TestController/redirectbacktest?BackURL=' . urlencode($externalAbsoluteUrl ?? ''));
        $this->assertEquals(
            Director::absoluteURL($link),
            $response->getHeader('Location'),
            "If BackURL Is external link, fall back to last url (Referer)"
        );
    }

    public function testSubActions()
    {
        // If a controller action returns another controller, ensure that the $action variable is correctly forwarded
        $response = $this->get("ContainerController/subcontroller/subaction");
        $this->assertEquals('subaction', $response->getBody());

        // Handle nested action
        $response = $this->get('ContainerController/subcontroller/substring/subvieweraction');
        $this->assertEquals('Hope this works', $response->getBody());
    }

    public function testSpecificHTTPMethods()
    {
        // 'GET /'
        $response = $this->get('HTTPMethodTestController');
        $this->assertEquals('Routed to getRoot', $response->getBody());

        // 'POST ' (legacy method of specifying root route)
        $response = $this->post('HTTPMethodTestController', ['dummy' => 'example']);
        $this->assertEquals('Routed to postLegacyRoot', $response->getBody());
    }
}
