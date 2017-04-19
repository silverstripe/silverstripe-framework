<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Cookie_Backend;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Tests\DirectorTest\TestController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestProcessor;
use SilverStripe\Security\Security;

/**
 * @todo test Director::alternateBaseFolder()
 */
class DirectorTest extends SapphireTest
{

    protected static $originalRequestURI;

    protected $originalProtocolHeaders = array();

    protected $originalGet = array();

    protected $originalSession = array();

    protected static $extra_controllers = [
        TestController::class,
    ];

    protected function setUp()
    {
        parent::setUp();

        // Hold the original request URI once so it doesn't get overwritten
        if (!self::$originalRequestURI) {
            self::$originalRequestURI = $_SERVER['REQUEST_URI'];
        }
        $_SERVER['REQUEST_URI'] = 'http://www.mysite.com';

        $this->originalGet = $_GET;
        $this->originalSession = $_SESSION;
        $_SESSION = array();

        $headers = array(
            'HTTP_X_FORWARDED_PROTOCOL', 'HTTPS', 'SSL'
        );

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $this->originalProtocolHeaders[$header] = $_SERVER[$header];
            }
        }

        Config::modify()->set(Director::class, 'alternate_base_url', '/');
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

    protected function tearDown()
    {
        $_GET = $this->originalGet;
        $_SESSION = $this->originalSession;

        // Reinstate the original REQUEST_URI after it was modified by some tests
        $_SERVER['REQUEST_URI'] = self::$originalRequestURI;

        if ($this->originalProtocolHeaders) {
            foreach ($this->originalProtocolHeaders as $header => $value) {
                $_SERVER[$header] = $value;
            }
        }


        parent::tearDown();
    }

    public function testFileExists()
    {
        $tempFileName = 'DirectorTest_testFileExists.tmp';
        $tempFilePath = TEMP_FOLDER . '/' . $tempFileName;

        // create temp file
        file_put_contents($tempFilePath, '');

        $this->assertTrue(
            Director::fileExists($tempFilePath),
            'File exist check with absolute path'
        );

        $this->assertTrue(
            Director::fileExists($tempFilePath . '?queryparams=1&foo[bar]=bar'),
            'File exist check with query params ignored'
        );

        unlink($tempFilePath);
    }

    public function testAbsoluteURL()
    {

        $rootURL = Director::protocolAndHost();
        $_SERVER['REQUEST_URI'] = "$rootURL/mysite/sub-page/";
        Director::config()->set('alternate_base_url', '/mysite/');

        //test empty / local urls
        foreach (array('', './', '.') as $url) {
            $this->assertEquals("$rootURL/mysite/", Director::absoluteURL($url, Director::BASE));
            $this->assertEquals("$rootURL/", Director::absoluteURL($url, Director::ROOT));
            $this->assertEquals("$rootURL/mysite/sub-page/", Director::absoluteURL($url, Director::REQUEST));
        }

        // Test site root url
        $this->assertEquals("$rootURL/", Director::absoluteURL('/'));

        // Test Director::BASE
        $this->assertEquals($rootURL, Director::absoluteURL($rootURL, Director::BASE));
        $this->assertEquals('http://www.mytest.com', Director::absoluteURL('http://www.mytest.com', Director::BASE));
        $this->assertEquals("$rootURL/test", Director::absoluteURL("$rootURL/test", Director::BASE));
        $this->assertEquals("$rootURL/root", Director::absoluteURL("/root", Director::BASE));
        $this->assertEquals("$rootURL/root/url", Director::absoluteURL("/root/url", Director::BASE));

        // Test Director::ROOT
        $this->assertEquals($rootURL, Director::absoluteURL($rootURL, Director::ROOT));
        $this->assertEquals('http://www.mytest.com', Director::absoluteURL('http://www.mytest.com', Director::ROOT));
        $this->assertEquals("$rootURL/test", Director::absoluteURL("$rootURL/test", Director::ROOT));
        $this->assertEquals("$rootURL/root", Director::absoluteURL("/root", Director::ROOT));
        $this->assertEquals("$rootURL/root/url", Director::absoluteURL("/root/url", Director::ROOT));

        // Test Director::REQUEST
        $this->assertEquals($rootURL, Director::absoluteURL($rootURL, Director::REQUEST));
        $this->assertEquals('http://www.mytest.com', Director::absoluteURL('http://www.mytest.com', Director::REQUEST));
        $this->assertEquals("$rootURL/test", Director::absoluteURL("$rootURL/test", Director::REQUEST));
        $this->assertEquals("$rootURL/root", Director::absoluteURL("/root", Director::REQUEST));
        $this->assertEquals("$rootURL/root/url", Director::absoluteURL("/root/url", Director::REQUEST));

        // Test evaluating relative urls relative to base (default)
        $this->assertEquals("$rootURL/mysite/test", Director::absoluteURL("test"));
        $this->assertEquals("$rootURL/mysite/test/url", Director::absoluteURL("test/url"));
        $this->assertEquals("$rootURL/mysite/test", Director::absoluteURL("test", Director::BASE));
        $this->assertEquals("$rootURL/mysite/test/url", Director::absoluteURL("test/url", Director::BASE));

        // Test evaluting relative urls relative to root
        $this->assertEquals("$rootURL/test", Director::absoluteURL("test", Director::ROOT));
        $this->assertEquals("$rootURL/test/url", Director::absoluteURL("test/url", Director::ROOT));

        // Test relative to requested page
        $this->assertEquals("$rootURL/mysite/sub-page/test", Director::absoluteURL("test", Director::REQUEST));
        $this->assertEquals("$rootURL/mysite/sub-page/test/url", Director::absoluteURL("test/url", Director::REQUEST));

        // Test that javascript links are not left intact
        $this->assertStringStartsNotWith('javascript', Director::absoluteURL('javascript:alert("attack")'));
        $this->assertStringStartsNotWith('alert', Director::absoluteURL('javascript:alert("attack")'));
        $this->assertStringStartsNotWith('javascript', Director::absoluteURL('alert("attack")'));
        $this->assertStringStartsNotWith('alert', Director::absoluteURL('alert("attack")'));
    }

    public function testAlternativeBaseURL()
    {
        // Get original protocol and hostname
        $rootURL = Director::protocolAndHost();

        // relative base URLs - you should end them in a /
        Director::config()->set('alternate_base_url', '/relativebase/');
        $_SERVER['REQUEST_URI'] = "$rootURL/relativebase/sub-page/";

        $this->assertEquals('/relativebase/', Director::baseURL());
        $this->assertEquals($rootURL . '/relativebase/', Director::absoluteBaseURL());
        $this->assertEquals(
            $rootURL . '/relativebase/subfolder/test',
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
     */
    public function testIsAbsolute()
    {
        $expected = array (
            'C:/something' => true,
            'd:\\'         => true,
            'e/'           => false,
            's:/directory' => true,
            '/var/www'     => true,
            '\\Something'  => true,
            'something/c:' => false,
            'folder'       => false,
            'a/c:/'        => false
        );

        foreach ($expected as $path => $result) {
            $this->assertEquals(Director::is_absolute($path), $result, "Test result for $path");
        }
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

    public function testMakeRelative()
    {
        $siteUrl = Director::absoluteBaseURL();
        $siteUrlNoProtocol = preg_replace('/https?:\/\//', '', $siteUrl);

        $this->assertEquals(Director::makeRelative("$siteUrl"), '');
        $this->assertEquals(Director::makeRelative("https://$siteUrlNoProtocol"), '');
        $this->assertEquals(Director::makeRelative("http://$siteUrlNoProtocol"), '');

        $this->assertEquals(Director::makeRelative("   $siteUrl/testpage   "), 'testpage');
        $this->assertEquals(Director::makeRelative("$siteUrlNoProtocol/testpage"), 'testpage');

        $this->assertEquals(Director::makeRelative('ftp://test.com'), 'ftp://test.com');
        $this->assertEquals(Director::makeRelative('http://test.com'), 'http://test.com');

        $this->assertEquals(Director::makeRelative('relative'), 'relative');
        $this->assertEquals(Director::makeRelative("$siteUrl/?url=http://test.com"), '?url=http://test.com');

        $this->assertEquals("test", Director::makeRelative("https://".$siteUrlNoProtocol."/test"));
        $this->assertEquals("test", Director::makeRelative("http://".$siteUrlNoProtocol."/test"));
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
        $this->assertFalse(Director::is_site_url("http://test.com?url=" . urlencode(Director::absoluteBaseURL())));
        $this->assertFalse(Director::is_site_url("//test.com?url=" . Director::absoluteBaseURL()));
    }

    /**
     * Tests isDev, isTest, isLive set from querystring
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

        // Test isDev=1
        $_GET['isDev'] = '1';
        $this->assertTrue(Director::isDev());
        $this->assertFalse(Director::isTest());
        $this->assertFalse(Director::isLive());

        // Test persistence
        unset($_GET['isDev']);
        $this->assertTrue(Director::isDev());
        $this->assertFalse(Director::isTest());
        $this->assertFalse(Director::isLive());

        // Test change to isTest
        $_GET['isTest'] = '1';
        $this->assertFalse(Director::isDev());
        $this->assertTrue(Director::isTest());
        $this->assertFalse(Director::isLive());

        // Test persistence
        unset($_GET['isTest']);
        $this->assertFalse(Director::isDev());
        $this->assertTrue(Director::isTest());
        $this->assertFalse(Director::isLive());
    }

    public function testResetGlobalsAfterTestRequest()
    {
        $_GET = array('somekey' => 'getvalue');
        $_POST = array('somekey' => 'postvalue');
        $_COOKIE = array('somekey' => 'cookievalue');

        $cookies = Injector::inst()->createWithArgs(
            Cookie_Backend::class,
            array(array('somekey' => 'sometestcookievalue'))
        );

        Director::test(
            'errorpage?somekey=sometestgetvalue',
            array('somekey' => 'sometestpostvalue'),
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

    public function testTestRequestCarriesGlobals()
    {
        $fixture = array('somekey' => 'sometestvalue');
        foreach (array('get', 'post') as $method) {
            foreach (array('return%sValue', 'returnRequestValue', 'returnCookieValue') as $testfunction) {
                $url = 'TestController/' . sprintf($testfunction, ucfirst($method))
                    . '?' . http_build_query($fixture);

                $getresponse = Director::test(
                    $url,
                    $fixture,
                    null,
                    strtoupper($method),
                    null,
                    null,
                    Injector::inst()->createWithArgs(Cookie_Backend::class, array($fixture))
                );

                $this->assertInstanceOf(HTTPResponse::class, $getresponse, 'Director::test() returns HTTPResponse');
                $this->assertEquals($fixture['somekey'], $getresponse->getBody(), 'Director::test() ' . $testfunction);
            }
        }
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
            array(
                'Controller' => TestController::class,
                'Action' => 'myaction',
                'ID' => 'myid',
                'OtherID' => 'myotherid',
                'Locale' => 'en_NZ'
            ),
            $request->params()
        );
    }

    public function testForceSSLProtectsEntireSite()
    {
        $_SERVER['REQUEST_URI'] = '/admin';
        $output = Director::forceSSL();
        $this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        $_SERVER['REQUEST_URI'] = Director::baseURL() . 'some-url';
        $output = Director::forceSSL();
        $this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

    public function testForceSSLOnTopLevelPagePattern()
    {
        $_SERVER['REQUEST_URI'] = Director::baseURL() . 'admin';
        $output = Director::forceSSL(array('/^admin/'));
        $this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

    public function testForceSSLOnSubPagesPattern()
    {
        $_SERVER['REQUEST_URI'] = Director::baseURL() . Config::inst()->get(Security::class, 'login_url');
        $output = Director::forceSSL(array('/^Security/'));
        $this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

    public function testForceSSLWithPatternDoesNotMatchOtherPages()
    {
        $_SERVER['REQUEST_URI'] = Director::baseURL() . 'normal-page';
        $output = Director::forceSSL(array('/^admin/'));
        $this->assertFalse($output);

        $_SERVER['REQUEST_URI'] = Director::baseURL() . 'just-another-page/sub-url';
        $output = Director::forceSSL(array('/^admin/', '/^Security/'));
        $this->assertFalse($output);
    }

    public function testForceSSLAlternateDomain()
    {
        Director::config()->set('alternate_base_url', '/');
        $_SERVER['REQUEST_URI'] = Director::baseURL() . 'admin';
        $output = Director::forceSSL(array('/^admin/'), 'secure.mysite.com');
        $this->assertEquals($output, 'https://secure.mysite.com/admin');
    }

    /**
     * @covers \SilverStripe\Control\Director::extract_request_headers()
     */
    public function testExtractRequestHeaders()
    {
        $request = array(
            'REDIRECT_STATUS'      => '200',
            'HTTP_HOST'            => 'host',
            'HTTP_USER_AGENT'      => 'User Agent',
            'HTTP_ACCEPT'          => 'text/html',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us',
            'HTTP_COOKIE'          => 'MyCookie=1',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'REQUEST_URI'          => '/',
            'SCRIPT_NAME'          => FRAMEWORK_DIR . '/main.php',
            'CONTENT_TYPE'         => 'text/xml',
            'CONTENT_LENGTH'       => 10
        );

        $headers = array(
            'Host'            => 'host',
            'User-Agent'      => 'User Agent',
            'Accept'          => 'text/html',
            'Accept-Language' => 'en-us',
            'Cookie'          => 'MyCookie=1',
            'Content-Type'    => 'text/xml',
            'Content-Length'  => '10'
        );

        $this->assertEquals($headers, Director::extract_request_headers($request));
    }

    public function testUnmatchedRequestReturns404()
    {
        // Remove non-tested rules
        $this->assertEquals(404, Director::test('no-route')->getStatusCode());
    }

    public function testIsHttps()
    {
        if (!TRUSTED_PROXY) {
            $this->markTestSkipped('Test cannot be run without trusted proxy');
        }
        // nothing available
        $headers = array(
            'HTTP_X_FORWARDED_PROTOCOL', 'HTTPS', 'SSL'
        );

        $origServer = $_SERVER;

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                unset($_SERVER['HTTP_X_FORWARDED_PROTOCOL']);
            }
        }

        $this->assertFalse(Director::is_https());

        $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] = 'https';
        $this->assertTrue(Director::is_https());

        $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] = 'http';
        $this->assertFalse(Director::is_https());

        $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] = 'ftp';
        $this->assertFalse(Director::is_https());

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue(Director::is_https());

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $this->assertFalse(Director::is_https());

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'ftp';
        $this->assertFalse(Director::is_https());

        $_SERVER['HTTP_FRONT_END_HTTPS'] = 'On';
        $this->assertTrue(Director::is_https());

        $_SERVER['HTTP_FRONT_END_HTTPS'] = 'Off';
        $this->assertFalse(Director::is_https());

        // https via HTTPS
        $_SERVER['HTTPS'] = 'true';
        $this->assertTrue(Director::is_https());

        $_SERVER['HTTPS'] = '1';
        $this->assertTrue(Director::is_https());

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(Director::is_https());

        // https via SSL
        $_SERVER['SSL'] = '';
        $this->assertTrue(Director::is_https());

        $_SERVER = $origServer;
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

        $processor = new RequestProcessor(array($filter));

        Injector::inst()->registerService($processor, RequestProcessor::class);

        Director::test('some-dummy-url');

        $this->assertEquals(1, $filter->preCalls);
        $this->assertEquals(1, $filter->postCalls);

        $filter->failPost = true;

        $this->setExpectedException(HTTPResponse_Exception::class);

        Director::test('some-dummy-url');

        $this->assertEquals(2, $filter->preCalls);
        $this->assertEquals(2, $filter->postCalls);

        $filter->failPre = true;

        Director::test('some-dummy-url');

        $this->assertEquals(3, $filter->preCalls);

        // preCall 'false' will trigger an exception and prevent post call execution
        $this->assertEquals(2, $filter->postCalls);
    }
}
