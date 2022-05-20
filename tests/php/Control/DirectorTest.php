<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Cookie_Backend;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;
use SilverStripe\Control\Middleware\RequestHandlerMiddlewareAdapter;
use SilverStripe\Control\Middleware\TrustedProxyMiddleware;
use SilverStripe\Control\RequestProcessor;
use SilverStripe\Control\Tests\DirectorTest\TestController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;

/**
 * @todo test Director::alternateBaseFolder()
 */
class DirectorTest extends SapphireTest
{
    protected static $extra_controllers = [
        TestController::class,
    ];

    private $originalEnvType;

    protected function setUp(): void
    {
        parent::setUp();
        Director::config()->set('alternate_base_url', 'http://www.mysite.com:9090/');

        $this->originalEnvType = Environment::getEnv('SS_ENVIRONMENT_TYPE');

        // Ensure redirects enabled on all environments and global state doesn't affect the tests
        CanonicalURLMiddleware::singleton()
            ->setForceSSL(null)
            ->setForceSSLDomain(null)
            ->setForceSSLPatterns([])
            ->setForceWWW(null)
            ->setEnabledEnvs(true);
        $this->expectedRedirect = null;
    }

    protected function tearDown(): void
    {
        Environment::setEnv('SS_ENVIRONMENT_TYPE', $this->originalEnvType);
        parent::tearDown();
    }

    protected function getExtraRoutes()
    {
        $rules = parent::getExtraRoutes();

        $rules['DirectorTestRule/$Action/$ID/$OtherID'] = TestController::class;
        $rules['en-nz/$Action/$ID/$OtherID'] = [
            'Controller' => TestController::class,
            'Locale' => 'en_NZ',
        ];
        return $rules;
    }

    protected function setUpRoutes()
    {
        // Don't merge with any existing rules
        Director::config()->set('rules', $this->getExtraRoutes());
    }

    public function testFileExists()
    {
        $tempFileName = 'DirectorTest_testFileExists.tmp';
        $tempFilePath = TEMP_PATH . DIRECTORY_SEPARATOR . $tempFileName;

        // create temp file
        file_put_contents($tempFilePath ?? '', '');

        $this->assertTrue(
            Director::fileExists($tempFilePath),
            'File exist check with absolute path'
        );

        $this->assertTrue(
            Director::fileExists($tempFilePath . '?queryparams=1&foo[bar]=bar'),
            'File exist check with query params ignored'
        );

        unlink($tempFilePath ?? '');
    }

    public function testAbsoluteURL()
    {
        Director::config()->set('alternate_base_url', 'http://www.mysite.com:9090/mysite/');
        $_SERVER['REQUEST_URI'] = "http://www.mysite.com:9090/mysite/sub-page/";

        //test empty / local urls
        foreach (['', './', '.'] as $url) {
            $this->assertEquals("http://www.mysite.com:9090/mysite/", Director::absoluteURL($url, Director::BASE));
            $this->assertEquals("http://www.mysite.com:9090/", Director::absoluteURL($url, Director::ROOT));
            $this->assertEquals("http://www.mysite.com:9090/mysite/sub-page/", Director::absoluteURL($url, Director::REQUEST));
        }

        // Test site root url
        $this->assertEquals("http://www.mysite.com:9090/", Director::absoluteURL('/'));

        // Test Director::BASE
        $this->assertEquals('http://www.mysite.com:9090/', Director::absoluteURL('http://www.mysite.com:9090/', Director::BASE));
        $this->assertEquals('http://www.mytest.com', Director::absoluteURL('http://www.mytest.com', Director::BASE));
        $this->assertEquals("http://www.mysite.com:9090/test", Director::absoluteURL("http://www.mysite.com:9090/test", Director::BASE));
        $this->assertEquals("http://www.mysite.com:9090/root", Director::absoluteURL("/root", Director::BASE));
        $this->assertEquals("http://www.mysite.com:9090/root/url", Director::absoluteURL("/root/url", Director::BASE));

        // Test Director::ROOT
        $this->assertEquals('http://www.mysite.com:9090/', Director::absoluteURL('http://www.mysite.com:9090/', Director::ROOT));
        $this->assertEquals('http://www.mytest.com', Director::absoluteURL('http://www.mytest.com', Director::ROOT));
        $this->assertEquals("http://www.mysite.com:9090/test", Director::absoluteURL("http://www.mysite.com:9090/test", Director::ROOT));
        $this->assertEquals("http://www.mysite.com:9090/root", Director::absoluteURL("/root", Director::ROOT));
        $this->assertEquals("http://www.mysite.com:9090/root/url", Director::absoluteURL("/root/url", Director::ROOT));

        // Test Director::REQUEST
        $this->assertEquals('http://www.mysite.com:9090/', Director::absoluteURL('http://www.mysite.com:9090/', Director::REQUEST));
        $this->assertEquals('http://www.mytest.com', Director::absoluteURL('http://www.mytest.com', Director::REQUEST));
        $this->assertEquals("http://www.mysite.com:9090/test", Director::absoluteURL("http://www.mysite.com:9090/test", Director::REQUEST));
        $this->assertEquals("http://www.mysite.com:9090/root", Director::absoluteURL("/root", Director::REQUEST));
        $this->assertEquals("http://www.mysite.com:9090/root/url", Director::absoluteURL("/root/url", Director::REQUEST));

        // Test evaluating relative urls relative to base (default)
        $this->assertEquals("http://www.mysite.com:9090/mysite/test", Director::absoluteURL("test"));
        $this->assertEquals("http://www.mysite.com:9090/mysite/test/url", Director::absoluteURL("test/url"));
        $this->assertEquals("http://www.mysite.com:9090/mysite/test", Director::absoluteURL("test", Director::BASE));
        $this->assertEquals("http://www.mysite.com:9090/mysite/test/url", Director::absoluteURL("test/url", Director::BASE));

        // Test evaluating relative urls relative to root
        $this->assertEquals("http://www.mysite.com:9090/test", Director::absoluteURL("test", Director::ROOT));
        $this->assertEquals("http://www.mysite.com:9090/test/url", Director::absoluteURL("test/url", Director::ROOT));

        // Test relative to requested page
        $this->assertEquals("http://www.mysite.com:9090/mysite/sub-page/test", Director::absoluteURL("test", Director::REQUEST));
        $this->assertEquals("http://www.mysite.com:9090/mysite/sub-page/test/url", Director::absoluteURL("test/url", Director::REQUEST));

        // Test that javascript links are not left intact
        $this->assertStringStartsNotWith('javascript', Director::absoluteURL('javascript:alert("attack")'));
        $this->assertStringStartsNotWith('alert', Director::absoluteURL('javascript:alert("attack")'));
        $this->assertStringStartsNotWith('javascript', Director::absoluteURL('alert("attack")'));
        $this->assertStringStartsNotWith('alert', Director::absoluteURL('alert("attack")'));
    }

    public function testAlternativeBaseURL()
    {
        // relative base URLs - you should end them in a /
        Director::config()->set('alternate_base_url', '/relativebase/');
        $_SERVER['HTTP_HOST'] = 'www.somesite.com';
        $_SERVER['REQUEST_URI'] = "/relativebase/sub-page/";

        $this->assertEquals('/relativebase/', Director::baseURL());
        $this->assertEquals('http://www.somesite.com/relativebase/', Director::absoluteBaseURL());
        $this->assertEquals(
            'http://www.somesite.com/relativebase/subfolder/test',
            Director::absoluteURL('subfolder/test')
        );

        // absolute base URLS with subdirectory - You should end them in a /
        Director::config()->set('alternate_base_url', 'http://www.example.org/relativebase/');
        $_SERVER['REQUEST_URI'] = "http://www.example.org/relativebase/sub-page/";
        $this->assertEquals('/relativebase/', Director::baseURL()); // Non-absolute url
        $this->assertEquals('http://www.example.org/relativebase/', Director::absoluteBaseURL());
        $this->assertEquals('http://www.example.org/relativebase/sub-page/', Director::absoluteURL('', Director::REQUEST));
        $this->assertEquals('http://www.example.org/relativebase/', Director::absoluteURL('', Director::BASE));
        $this->assertEquals('http://www.example.org/', Director::absoluteURL('', Director::ROOT));
        $this->assertEquals(
            'http://www.example.org/relativebase/sub-page/subfolder/test',
            Director::absoluteURL('subfolder/test', Director::REQUEST)
        );
        $this->assertEquals(
            'http://www.example.org/subfolder/test',
            Director::absoluteURL('subfolder/test', Director::ROOT)
        );
        $this->assertEquals(
            'http://www.example.org/relativebase/subfolder/test',
            Director::absoluteURL('subfolder/test', Director::BASE)
        );

        // absolute base URLs - you should end them in a /
        Director::config()->set('alternate_base_url', 'http://www.example.org/');
        $_SERVER['REQUEST_URI'] = "http://www.example.org/sub-page/";
        $this->assertEquals('/', Director::baseURL()); // Non-absolute url
        $this->assertEquals('http://www.example.org/', Director::absoluteBaseURL());
        $this->assertEquals('http://www.example.org/sub-page/', Director::absoluteURL('', Director::REQUEST));
        $this->assertEquals('http://www.example.org/', Director::absoluteURL('', Director::BASE));
        $this->assertEquals('http://www.example.org/', Director::absoluteURL('', Director::ROOT));
        $this->assertEquals(
            'http://www.example.org/sub-page/subfolder/test',
            Director::absoluteURL('subfolder/test', Director::REQUEST)
        );
        $this->assertEquals(
            'http://www.example.org/subfolder/test',
            Director::absoluteURL('subfolder/test', Director::ROOT)
        );
        $this->assertEquals(
            'http://www.example.org/subfolder/test',
            Director::absoluteURL('subfolder/test', Director::BASE)
        );
    }

    /**
     * Tests that {@link Director::is_absolute()} works under different environment types
     * @dataProvider provideAbsolutePaths
     */
    public function testIsAbsolute($path, $result)
    {
        $this->assertEquals($result, Director::is_absolute($path));
    }

    public function provideAbsolutePaths()
    {
        return [
            ['C:/something', true],
            ['d:\\', true],
            ['e/', false],
            ['s:/directory', true],
            ['/var/www', true],
            ['\\Something', true],
            ['something/c:', false],
            ['folder', false],
            ['a/c:/', false],
        ];
    }

    public function testIsAbsoluteUrl()
    {
        $this->assertTrue(Director::is_absolute_url('http://test.com/testpage'));
        $this->assertTrue(Director::is_absolute_url('ftp://test.com'));
        $this->assertFalse(Director::is_absolute_url('test.com/testpage'));
        $this->assertFalse(Director::is_absolute_url('/relative'));
        $this->assertFalse(Director::is_absolute_url('relative'));
        $this->assertFalse(Director::is_absolute_url("/relative/?url=http://foo.com"));
        $this->assertFalse(Director::is_absolute_url("/relative/#http://foo.com"));
        $this->assertTrue(Director::is_absolute_url("https://test.com/?url=http://foo.com"));
        $this->assertTrue(Director::is_absolute_url("trickparseurl:http://test.com"));
        $this->assertTrue(Director::is_absolute_url('//test.com'));
        $this->assertTrue(Director::is_absolute_url('/////test.com'));
        $this->assertTrue(Director::is_absolute_url('  ///test.com'));
        $this->assertTrue(Director::is_absolute_url('http:test.com'));
        $this->assertTrue(Director::is_absolute_url('//http://test.com'));
    }

    public function testHostPortParts()
    {
        $this->assertEquals('www.mysite.com:9090', Director::host());
        $this->assertEquals('www.mysite.com', Director::hostName());
        $this->assertEquals('9090', Director::port());
    }

    public function testIsRelativeUrl()
    {
        $this->assertFalse(Director::is_relative_url('http://test.com'));
        $this->assertFalse(Director::is_relative_url('https://test.com'));
        $this->assertFalse(Director::is_relative_url('   https://test.com/testpage   '));
        $this->assertTrue(Director::is_relative_url('test.com/testpage'));
        $this->assertFalse(Director::is_relative_url('ftp://test.com'));
        $this->assertTrue(Director::is_relative_url('/relative'));
        $this->assertTrue(Director::is_relative_url('relative'));
        $this->assertTrue(Director::is_relative_url('/relative/?url=http://test.com'));
        $this->assertTrue(Director::is_relative_url('/relative/#=http://test.com'));
    }

    /**
     * @return array
     */
    public function providerMakeRelative()
    {
        return [
            // Resilience to slash position
            [
                'http://www.mysite.com:9090/base/folder',
                'http://www.mysite.com:9090/base/folder',
                ''
            ],
            [
                'http://www.mysite.com:9090/base/folder',
                'http://www.mysite.com:9090/base/folder/',
                ''
            ],
            [
                'http://www.mysite.com:9090/base/folder/',
                'http://www.mysite.com:9090/base/folder',
                ''
            ],
            [
                'http://www.mysite.com:9090/',
                'http://www.mysite.com:9090/',
                ''
            ],
            [
                'http://www.mysite.com:9090/',
                'http://www.mysite.com',
                ''
            ],
            [
                'http://www.mysite.com',
                'http://www.mysite.com:9090/',
                ''
            ],
            [
                'http://www.mysite.com:9090/base/folder',
                'http://www.mysite.com:9090/base/folder/page',
                'page'
            ],
            [
                'http://www.mysite.com:9090/',
                'http://www.mysite.com:9090/page/',
                'page/'
            ],
            // Parsing protocol safely
            [
                'http://www.mysite.com:9090/base/folder',
                'https://www.mysite.com:9090/base/folder',
                ''
            ],
            [
                'https://www.mysite.com:9090/base/folder',
                'http://www.mysite.com:9090/base/folder/testpage',
                'testpage'
            ],
            [
                'http://www.mysite.com:9090/base/folder',
                '//www.mysite.com:9090/base/folder/testpage',
                'testpage'
            ],
            // Dirty input
            [
                'http://www.mysite.com:9090/base/folder',
                '    https://www.mysite.com:9090/base/folder/testpage    ',
                'testpage'
            ],
            [
                'http://www.mysite.com:9090/base/folder',
                '//www.mysite.com:9090/base//folder/testpage//subpage',
                'testpage/subpage'
            ],
            // Non-http protocol isn't modified
            [
                'http://www.mysite.com:9090/base/folder',
                'ftp://test.com',
                'ftp://test.com'
            ],
            // Alternate hostnames are redirected
            [
                'https://www.mysite.com:9090/base/folder',
                'http://mysite.com:9090/base/folder/testpage',
                'testpage'
            ],
            [
                'http://www.otherdomain.com/base/folder',
                '//www.mysite.com:9090/base/folder/testpage',
                'testpage'
            ],
            // Base folder is found
            [
                'http://www.mysite.com:9090/base/folder',
                BASE_PATH . '/some/file.txt',
                'some/file.txt',
            ],
            // public folder is found
            [
                'http://www.mysite.com:9090/base/folder',
                PUBLIC_PATH . '/some/file.txt',
                'some/file.txt',
            ],
            // querystring is protected
            [
                'http://www.mysite.com:9090/base/folder',
                '//www.mysite.com:9090/base//folder/testpage//subpage?args=hello',
                'testpage/subpage?args=hello'
            ],
            [
                'http://www.mysite.com:9090/base/folder',
                '//www.mysite.com:9090/base//folder/?args=hello',
                '?args=hello'
            ],
        ];
    }

    /**
     * @dataProvider providerMakeRelative
     * @param string $baseURL Site base URL
     * @param string $requestURL Request URL
     * @param string $relativeURL Expected relative URL
     */
    public function testMakeRelative($baseURL, $requestURL, $relativeURL)
    {
        Director::config()->set('alternate_base_url', $baseURL);
        $actualRelative = Director::makeRelative($requestURL);
        $this->assertEquals(
            $relativeURL,
            $actualRelative,
            "Expected relativeURL of {$requestURL} to be {$relativeURL}"
        );
    }

    /**
     * Mostly tested by {@link testIsRelativeUrl()},
     * just adding the host name matching aspect here.
     */
    public function testIsSiteUrl()
    {
        $this->assertFalse(Director::is_site_url("http://test.com"));
        $this->assertTrue(Director::is_site_url(Director::absoluteBaseURL()));
        $this->assertFalse(Director::is_site_url("http://test.com?url=" . Director::absoluteBaseURL()));
        $this->assertFalse(Director::is_site_url("http://test.com?url=" . urlencode(Director::absoluteBaseURL() ?? '')));
        $this->assertFalse(Director::is_site_url("//test.com?url=" . Director::absoluteBaseURL()));
        $this->assertFalse(Director::is_site_url('http://google.com\@test.com'));
        $this->assertFalse(Director::is_site_url('http://google.com/@test.com'));
        $this->assertFalse(Director::is_site_url('http://google.com:pass\@test.com'));
        $this->assertFalse(Director::is_site_url('http://google.com:pass/@test.com'));
    }

    /**
     * Tests isDev, isTest, isLive cannot be set from querystring
     */
    public function testQueryIsEnvironment()
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        // Reset
        unset($_SESSION['isDev']);
        unset($_SESSION['isLive']);
        unset($_GET['isTest']);
        unset($_GET['isDev']);

        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->setEnvironment(null);
        Environment::setEnv('SS_ENVIRONMENT_TYPE', Kernel::LIVE);

        $this->assertTrue(Director::isLive());

        // Test isDev=1
        $_GET['isDev'] = '1';
        $this->assertFalse(Director::isDev());
        $this->assertFalse(Director::isTest());
        $this->assertTrue(Director::isLive());

        // Test persistence
        unset($_GET['isDev']);
        $this->assertFalse(Director::isDev());
        $this->assertFalse(Director::isTest());
        $this->assertTrue(Director::isLive());

        // Test change to isTest
        $_GET['isTest'] = '1';
        $this->assertFalse(Director::isDev());
        $this->assertFalse(Director::isTest());
        $this->assertTrue(Director::isLive());

        // Test persistence
        unset($_GET['isTest']);
        $this->assertFalse(Director::isDev());
        $this->assertFalse(Director::isTest());
        $this->assertTrue(Director::isLive());
    }

    public function testResetGlobalsAfterTestRequest()
    {
        $_GET = ['somekey' => 'getvalue'];
        $_POST = ['somekey' => 'postvalue'];
        $_COOKIE = ['somekey' => 'cookievalue'];

        $cookies = Injector::inst()->createWithArgs(
            Cookie_Backend::class,
            [['somekey' => 'sometestcookievalue']]
        );

        Director::test(
            'errorpage?somekey=sometestgetvalue',
            ['somekey' => 'sometestpostvalue'],
            null,
            null,
            null,
            null,
            $cookies
        );

        $this->assertEquals(
            'getvalue',
            $_GET['somekey'],
            '$_GET reset to original value after Director::test()'
        );
        $this->assertEquals(
            'postvalue',
            $_POST['somekey'],
            '$_POST reset to original value after Director::test()'
        );
        $this->assertEquals(
            'cookievalue',
            $_COOKIE['somekey'],
            '$_COOKIE reset to original value after Director::test()'
        );
    }

    public function providerTestTestRequestCarriesGlobals()
    {
        $tests = [];
        $fixture = ['somekey' => 'sometestvalue'];
        foreach (['get', 'post'] as $method) {
            foreach (['return%sValue', 'returnRequestValue', 'returnCookieValue'] as $testfunction) {
                $url = 'TestController/' . sprintf($testfunction ?? '', ucfirst($method ?? ''))
                    . '?' . http_build_query($fixture ?? []);
                $tests[] = [$url, $fixture, $method];
            }
        }
        return $tests;
    }

    /**
     * @dataProvider providerTestTestRequestCarriesGlobals
     * @param $url
     * @param $fixture
     * @param $method
     */
    public function testTestRequestCarriesGlobals($url, $fixture, $method)
    {
        $getresponse = Director::test(
            $url,
            $fixture,
            null,
            strtoupper($method ?? ''),
            null,
            null,
            Injector::inst()->createWithArgs(Cookie_Backend::class, [$fixture])
        );

        $this->assertInstanceOf(HTTPResponse::class, $getresponse, 'Director::test() returns HTTPResponse');
        $this->assertEquals($fixture['somekey'], $getresponse->getBody(), "Director::test({$url}, {$method})");
    }

    /**
     * Tests that additional parameters specified in the routing table are
     * saved in the request
     */
    public function testRouteParams()
    {
        /** @var HTTPRequest $request */
        Director::test('en-nz/myaction/myid/myotherid', null, null, null, null, null, null, $request);

        $this->assertEquals(
            [
                'Controller' => TestController::class,
                'Action' => 'myaction',
                'ID' => 'myid',
                'OtherID' => 'myotherid',
                'Locale' => 'en_NZ'
            ],
            $request->params()
        );
    }

    public function testForceWWW()
    {
        $this->expectExceptionRedirect('http://www.mysite.com:9090/some-url');
        Director::mockRequest(function ($request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            Director::forceWWW();
        }, 'http://mysite.com:9090/some-url');
    }

    public function testPromisedForceWWW()
    {
        Director::forceWWW();

        // Flag is set but not redirected yet
        $middleware = CanonicalURLMiddleware::singleton();
        $this->assertTrue($middleware->getForceWWW());

        // Middleware forces the redirection eventually
        /** @var HTTPResponse $response */
        $response = Director::mockRequest(function ($request) use ($middleware) {
            return $middleware->process($request, function ($request) {
                return null;
            });
        }, 'http://mysite.com:9090/some-url');

        // Middleware returns non-exception redirect
        $this->assertEquals('http://www.mysite.com:9090/some-url', $response->getHeader('Location'));
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testForceSSLProtectsEntireSite()
    {
        $this->expectExceptionRedirect('https://www.mysite.com:9090/some-url');
        Director::mockRequest(function ($request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            Director::forceSSL();
        }, 'http://www.mysite.com:9090/some-url');
    }

    public function testPromisedForceSSL()
    {
        Director::forceSSL();

        // Flag is set but not redirected yet
        $middleware = CanonicalURLMiddleware::singleton();
        $this->assertTrue($middleware->getForceSSL());

        // Middleware forces the redirection eventually
        /** @var HTTPResponse $response */
        $response = Director::mockRequest(function ($request) use ($middleware) {
            return $middleware->process($request, function ($request) {
                return null;
            });
        }, 'http://www.mysite.com:9090/some-url');

        // Middleware returns non-exception redirect
        $this->assertInstanceOf(HTTPResponse::class, $response);
        $this->assertEquals('https://www.mysite.com:9090/some-url', $response->getHeader('Location'));
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testForceSSLOnTopLevelPagePattern()
    {
        // Expect admin to trigger redirect
        $this->expectExceptionRedirect('https://www.mysite.com:9090/admin');
        Director::mockRequest(function (HTTPRequest $request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            Director::forceSSL(['/^admin/']);
        }, 'http://www.mysite.com:9090/admin');
    }

    public function testForceSSLOnSubPagesPattern()
    {
        // Expect to redirect to security login page
        $this->expectExceptionRedirect('https://www.mysite.com:9090/Security/login');
        Director::mockRequest(function (HTTPRequest $request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            Director::forceSSL(['/^Security/']);
        }, 'http://www.mysite.com:9090/Security/login');
    }

    public function testForceSSLWithPatternDoesNotMatchOtherPages()
    {
        // Not on same url should not trigger redirect
        $response = Director::mockRequest(function (HTTPRequest $request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            Director::forceSSL(['/^admin/']);
        }, 'http://www.mysite.com:9090/normal-page');
        $this->assertNull($response, 'Non-matching patterns do not trigger redirect');

        // nested url should not triger redirect either
        $response = Director::mockRequest(function (HTTPRequest $request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            Director::forceSSL(['/^admin/', '/^Security/']);
        }, 'http://www.mysite.com:9090/just-another-page/sub-url');
        $this->assertNull($response, 'Non-matching patterns do not trigger redirect');
    }

    public function testForceSSLAlternateDomain()
    {
        // Ensure that forceSSL throws the appropriate exception
        $this->expectExceptionRedirect('https://secure.mysite.com/admin');
        Director::mockRequest(function (HTTPRequest $request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            return Director::forceSSL(['/^admin/'], 'secure.mysite.com');
        }, 'http://www.mysite.com:9090/admin');
    }

    public function testForceSSLAlternateDomainWithPort()
    {
        // Ensure that forceSSL throws the appropriate exception
        $this->expectExceptionRedirect('https://secure.mysite.com:81/admin');
        Director::mockRequest(function (HTTPRequest $request) {
            Injector::inst()->registerService($request, HTTPRequest::class);
            return Director::forceSSL(['/^admin/'], 'secure.mysite.com:81');
        }, 'http://www.mysite.com:9090/admin');
    }

    /**
     * Test that combined forceWWW and forceSSL combine safely
     */
    public function testForceSSLandForceWWW()
    {
        Director::forceWWW();
        Director::forceSSL();

        // Flag is set but not redirected yet
        $middleware = CanonicalURLMiddleware::singleton();
        $this->assertTrue($middleware->getForceWWW());
        $this->assertTrue($middleware->getForceSSL());

        // Middleware forces the redirection eventually
        /** @var HTTPResponse $response */
        $response = Director::mockRequest(function ($request) use ($middleware) {
            return $middleware->process($request, function ($request) {
                return null;
            });
        }, 'http://mysite.com:9090/some-url');

        // Middleware returns non-exception redirect
        $this->assertEquals('https://www.mysite.com:9090/some-url', $response->getHeader('Location'));
        $this->assertEquals(301, $response->getStatusCode());
    }

    /**
     * Set url to redirect to
     *
     * @var string
     */
    protected $expectedRedirect = null;

    /**
     * Expects this test to throw a HTTPResponse_Exception with the given redirect
     *
     * @param string $url
     */
    protected function expectExceptionRedirect($url)
    {
        $this->expectedRedirect = $url;
    }

    protected function runTest()
    {
        try {
            $result = parent::runTest();
            if ($this->expectedRedirect) {
                $this->fail("Expected to redirect to {$this->expectedRedirect} but no redirect found");
            }
            return $result;
        } catch (HTTPResponse_Exception $exception) {
            // Check URL
            if ($this->expectedRedirect) {
                $url = $exception->getResponse()->getHeader('Location');
                $this->assertEquals($this->expectedRedirect, $url, "Expected to redirect to {$this->expectedRedirect}");
                return null;
            } else {
                throw $exception;
            }
        }
    }

    public function testUnmatchedRequestReturns404()
    {
        // Remove non-tested rules
        $this->assertEquals(404, Director::test('no-route')->getStatusCode());
    }

    public function testIsHttps()
    {
        // Trust all IPs for this test
        /** @var TrustedProxyMiddleware $trustedProxyMiddleware */
        $trustedProxyMiddleware
            = Injector::inst()->get(TrustedProxyMiddleware::class);
        $trustedProxyMiddleware->setTrustedProxyIPs('*');

        // Clear alternate_base_url for this test
        Director::config()->remove('alternate_base_url');

        // nothing available
        $headers = [
            'HTTP_X_FORWARDED_PROTOCOL', 'HTTPS', 'SSL'
        ];
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                unset($_SERVER['HTTP_X_FORWARDED_PROTOCOL']);
            }
        }

        $this->assertEquals(
            'no',
            Director::test('TestController/returnIsSSL')->getBody()
        );

        $this->assertEquals(
            'yes',
            Director::test(
                'TestController/returnIsSSL',
                null,
                null,
                null,
                null,
                ['X-Forwarded-Protocol' => 'https']
            )->getBody()
        );

        $this->assertEquals(
            'no',
            Director::test(
                'TestController/returnIsSSL',
                null,
                null,
                null,
                null,
                ['X-Forwarded-Protocol' => 'http']
            )->getBody()
        );

        $this->assertEquals(
            'no',
            Director::test(
                'TestController/returnIsSSL',
                null,
                null,
                null,
                null,
                ['X-Forwarded-Protocol' => 'ftp']
            )->getBody()
        );

        // https via HTTPS
        $_SERVER['HTTPS'] = 'true';
        $this->assertEquals(
            'yes',
            Director::test('TestController/returnIsSSL')->getBody()
        );

        $_SERVER['HTTPS'] = '1';
        $this->assertEquals(
            'yes',
            Director::test('TestController/returnIsSSL')->getBody()
        );

        $_SERVER['HTTPS'] = 'off';
        $this->assertEquals(
            'no',
            Director::test('TestController/returnIsSSL')->getBody()
        );

        // https via SSL
        $_SERVER['SSL'] = '';
        $this->assertEquals(
            'yes',
            Director::test('TestController/returnIsSSL')->getBody()
        );
    }

    public function testTestIgnoresHashes()
    {
        //test that hashes are ignored
        $url = "TestController/returnGetValue?somekey=key";
        $hash = "#test";
        /** @var HTTPRequest $request */
        $response = Director::test($url . $hash, null, null, null, null, null, null, $request);
        $this->assertFalse($response->isError());
        $this->assertEquals('key', $response->getBody());
        $this->assertEquals($request->getURL(true), $url);

        //test encoded hashes are accepted
        $url = "TestController/returnGetValue?somekey=test%23key";
        $response = Director::test($url, null, null, null, null, null, null, $request);
        $this->assertFalse($response->isError());
        $this->assertEquals('test#key', $response->getBody());
        $this->assertEquals($request->getURL(true), $url);
    }

    public function testRequestFilterInDirectorTest()
    {
        $filter = new DirectorTest\TestRequestFilter;

        $processor = new RequestProcessor([$filter]);

        $middlewares = Director::singleton()->getMiddlewares();
        $middlewares['RequestProcessorMiddleware'] = $processor;
        Director::singleton()->setMiddlewares($middlewares);

        $response = Director::test('some-dummy-url');
        $this->assertEquals(404, $response->getStatusCode());

        $this->assertEquals(1, $filter->preCalls);
        $this->assertEquals(1, $filter->postCalls);

        $filter->failPost = true;

        $response = Director::test('some-dummy-url');
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(_t(Director::class . '.REQUEST_ABORTED', 'Request aborted'), $response->getBody());

        $this->assertEquals(2, $filter->preCalls);
        $this->assertEquals(2, $filter->postCalls);

        $filter->failPre = true;

        $response = Director::test('some-dummy-url');
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(_t(Director::class . '.INVALID_REQUEST', 'Invalid request'), $response->getBody());

        $this->assertEquals(3, $filter->preCalls);

        // preCall 'true' will trigger an exception and prevent post call execution
        $this->assertEquals(2, $filter->postCalls);
    }

    public function testGlobalMiddleware()
    {
        $middleware = new DirectorTest\TestMiddleware;
        Director::singleton()->setMiddlewares([$middleware]);

        $response = Director::test('some-dummy-url');
        $this->assertEquals(404, $response->getStatusCode());

        // Both triggered
        $this->assertEquals(1, $middleware->preCalls);
        $this->assertEquals(1, $middleware->postCalls);

        $middleware->failPost = true;

        $response = Director::test('some-dummy-url');
        $this->assertEquals(500, $response->getStatusCode());

        // Both triggered
        $this->assertEquals(2, $middleware->preCalls);
        $this->assertEquals(2, $middleware->postCalls);

        $middleware->failPre = true;

        $response = Director::test('some-dummy-url');
        $this->assertEquals(400, $response->getStatusCode());

        // Pre triggered, post not
        $this->assertEquals(3, $middleware->preCalls);
        $this->assertEquals(2, $middleware->postCalls);
    }

    public function testRouteSpecificMiddleware()
    {
        // Inject adapter in place of controller
        $specificMiddleware = new DirectorTest\TestMiddleware;
        Injector::inst()->registerService($specificMiddleware, 'SpecificMiddleware');

        // Register adapter as factory for creating this controller
        Config::modify()->merge(
            Injector::class,
            'ControllerWithMiddleware',
            [
                'class' => RequestHandlerMiddlewareAdapter::class,
                'constructor' => [
                    '%$' . TestController::class
                ],
                'properties' => [
                    'Middlewares' => [
                        '%$SpecificMiddleware',
                    ],
                ],
            ]
        );

        // Global middleware
        $middleware = new DirectorTest\TestMiddleware;
        Director::singleton()->setMiddlewares([$middleware]);

        // URL rules, one of which has a specific middleware
        Config::modify()->set(
            Director::class,
            'rules',
            [
                'url-one' => TestController::class,
                'url-two' => [
                    'Controller' => 'ControllerWithMiddleware',
                ],
            ]
        );

        // URL without a route-specific middleware
        Director::test('url-one');

        // Only the global middleware triggered
        $this->assertEquals(1, $middleware->preCalls);
        $this->assertEquals(0, $specificMiddleware->postCalls);

        Director::test('url-two');

        // Both triggered on the url with the specific middleware applied
        $this->assertEquals(2, $middleware->preCalls);
        $this->assertEquals(1, $specificMiddleware->postCalls);
    }

    /**
     * If using phpdbg it returns itself instead of "cli" from php_sapi_name()
     */
    public function testIsCli()
    {
        $this->assertTrue(Director::is_cli(), 'is_cli should be true for PHP CLI and phpdbg');
    }

    public function testMockRequest()
    {
        Director::config()->set('alternate_base_url', 'http://www.mysite.com:9090/some-subdir/');

        // Can handle root-relative $url
        Director::mockRequest(function (HTTPRequest $request) {
            $this->assertEquals('some-page/nested', $request->getURL());
            $this->assertEquals(1, $request->getVar('query'));
            $this->assertEquals('/some-subdir/some-page/nested', $_SERVER['REQUEST_URI']);
        }, '/some-subdir/some-page/nested?query=1');

        // Can handle absolute $url
        Director::mockRequest(function (HTTPRequest $request) {
            $this->assertEquals('some-page/nested', $request->getURL());
            $this->assertEquals(1, $request->getVar('query'));
            $this->assertEquals('/some-subdir/some-page/nested', $_SERVER['REQUEST_URI']);
        }, 'http://www.mysite.com:9090/some-subdir/some-page/nested?query=1');

        // Can handle relative $url
        Director::mockRequest(function (HTTPRequest $request) {
            $this->assertEquals('some-page/nested', $request->getURL());
            $this->assertEquals(1, $request->getVar('query'));
            $this->assertEquals('/some-subdir/some-page/nested', $_SERVER['REQUEST_URI']);
        }, 'some-page/nested?query=1');
    }
}
