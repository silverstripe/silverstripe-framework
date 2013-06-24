<?php
/**
 * @package framework
 * @subpackage tests
 * 
 * @todo test Director::alternateBaseFolder()
 */
class DirectorTest extends SapphireTest {

	protected static $originalRequestURI;

	protected $originalProtocolHeaders = array();

	public function setUp() {
		parent::setUp();

		// Hold the original request URI once so it doesn't get overwritten
		if(!self::$originalRequestURI) {
			self::$originalRequestURI = $_SERVER['REQUEST_URI'];
		}
		
		Config::inst()->update('Director', 'rules', array(
			'DirectorTestRule/$Action/$ID/$OtherID' => 'DirectorTestRequest_Controller',
			'en-nz/$Action/$ID/$OtherID' => array(
				'Controller' => 'DirectorTestRequest_Controller',
				'Locale' => 'en_NZ'
			)
		));

		$headers = array(
			'HTTP_X_FORWARDED_PROTOCOL', 'HTTPS', 'SSL'
		);

		foreach($headers as $header) {
			if(isset($_SERVER[$header])) {
				$this->originalProtocolHeaders[$header] = $_SERVER[$header];
			}
		}
	}
	
	public function tearDown() {
		// TODO Remove director rule, currently API doesnt allow this
		
		// Reinstate the original REQUEST_URI after it was modified by some tests
		$_SERVER['REQUEST_URI'] = self::$originalRequestURI;

		if($this->originalProtocolHeaders) {
			foreach($this->originalProtocolHeaders as $header => $value) {
				$_SERVER[$header] = $value;
			}
		}

		parent::tearDown();
	}
	
	public function testFileExists() {
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

	public function testAlternativeBaseURL() {
		// relative base URLs - you should end them in a /
		Config::inst()->update('Director', 'alternate_base_url', '/relativebase/');
		$this->assertEquals('/relativebase/', Director::baseURL());
		$this->assertEquals(Director::protocolAndHost() . '/relativebase/', Director::absoluteBaseURL());
		$this->assertEquals(Director::protocolAndHost() . '/relativebase/subfolder/test',
			Director::absoluteURL('subfolder/test'));

		// absolute base URLs - you should end them in a /
		Config::inst()->update('Director', 'alternate_base_url', 'http://www.example.org/');
		$this->assertEquals('http://www.example.org/', Director::baseURL());
		$this->assertEquals('http://www.example.org/', Director::absoluteBaseURL());
		$this->assertEquals('http://www.example.org/subfolder/test', Director::absoluteURL('subfolder/test'));

		// Setting it to false restores functionality
		Config::inst()->update('Director', 'alternate_base_url', false);
		$this->assertEquals(BASE_URL.'/', Director::baseURL());
		$this->assertEquals(Director::protocolAndHost().BASE_URL.'/', Director::absoluteBaseURL(BASE_URL));
		$this->assertEquals(Director::protocolAndHost().BASE_URL . '/subfolder/test',
			Director::absoluteURL('subfolder/test'));
	}
	
	/**
	 * Tests that {@link Director::is_absolute()} works under different environment types
	 */
	public function testIsAbsolute() {
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
		
		foreach($expected as $path => $result) {
			$this->assertEquals(Director::is_absolute($path), $result, "Test result for $path");
		}
	}
	
	public function testIsAbsoluteUrl() {
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
	
	public function testIsRelativeUrl() {
		$siteUrl = Director::absoluteBaseURL();
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
	
	public function testMakeRelative() {
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
	public function testIsSiteUrl() {
		$this->assertFalse(Director::is_site_url("http://test.com"));
		$this->assertTrue(Director::is_site_url(Director::absoluteBaseURL()));
		$this->assertFalse(Director::is_site_url("http://test.com?url=" . Director::absoluteBaseURL()));
		$this->assertFalse(Director::is_site_url("http://test.com?url=" . urlencode(Director::absoluteBaseURL())));
		$this->assertFalse(Director::is_site_url("//test.com?url=" . Director::absoluteBaseURL()));
	}
	
	public function testResetGlobalsAfterTestRequest() {
		$_GET = array('somekey' => 'getvalue');
		$_POST = array('somekey' => 'postvalue');
		$_COOKIE = array('somekey' => 'cookievalue');

		$getresponse = Director::test('errorpage?somekey=sometestgetvalue', array('somekey' => 'sometestpostvalue'),
			null, null, null, null, array('somekey' => 'sometestcookievalue'));

		$this->assertEquals('getvalue', $_GET['somekey'],
			'$_GET reset to original value after Director::test()');
		$this->assertEquals('postvalue', $_POST['somekey'],
			'$_POST reset to original value after Director::test()');
		$this->assertEquals('cookievalue', $_COOKIE['somekey'],
			'$_COOKIE reset to original value after Director::test()');
	}
	
	public function testTestRequestCarriesGlobals() {
		$fixture = array('somekey' => 'sometestvalue');
		foreach(array('get', 'post') as $method) {
			foreach(array('return%sValue', 'returnRequestValue', 'returnCookieValue') as $testfunction) {
				$url = 'DirectorTestRequest_Controller/' . sprintf($testfunction, ucfirst($method))
					. '?' . http_build_query($fixture);
				$getresponse = Director::test($url, $fixture, null, strtoupper($method), null, null, $fixture);

				$this->assertInstanceOf('SS_HTTPResponse', $getresponse, 'Director::test() returns SS_HTTPResponse');
				$this->assertEquals($fixture['somekey'], $getresponse->getBody(), 'Director::test() ' . $testfunction);
			}
		}
	}
	
	/**
	 * Tests that additional parameters specified in the routing table are 
	 * saved in the request 
	 */
	public function testRouteParams() {
		Director::test('en-nz/myaction/myid/myotherid', null, null, null, null, null, null, $request);
		
		$this->assertEquals(
			$request->params(), 
			array(
				'Controller' => 'DirectorTestRequest_Controller',
				'Action' => 'myaction', 
				'ID' => 'myid', 
				'OtherID' => 'myotherid',
				'Locale' => 'en_NZ'
			)
		);
	}

	public function testForceSSLProtectsEntireSite() {
		$_SERVER['REQUEST_URI'] = Director::baseURL() . 'admin';
		$output = Director::forceSSL();
		$this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		$_SERVER['REQUEST_URI'] = Director::baseURL() . 'some-url';
		$output = Director::forceSSL();
		$this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}

	public function testForceSSLOnTopLevelPagePattern() {
		$_SERVER['REQUEST_URI'] = Director::baseURL() . 'admin';
		$output = Director::forceSSL(array('/^admin/'));
		$this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}

	public function testForceSSLOnSubPagesPattern() {
		$_SERVER['REQUEST_URI'] = Director::baseURL() . Config::inst()->get('Security', 'login_url');
		$output = Director::forceSSL(array('/^Security/'));
		$this->assertEquals($output, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}

	public function testForceSSLWithPatternDoesNotMatchOtherPages() {
		$_SERVER['REQUEST_URI'] = Director::baseURL() . 'normal-page';
		$output = Director::forceSSL(array('/^admin/'));
		$this->assertFalse($output);

		$_SERVER['REQUEST_URI'] = Director::baseURL() . 'just-another-page/sub-url';
		$output = Director::forceSSL(array('/^admin/', '/^Security/'));
		$this->assertFalse($output);
	}

	public function testForceSSLAlternateDomain() {
		Config::inst()->update('Director', 'alternate_base_url', '/');
		$_SERVER['REQUEST_URI'] = Director::baseURL() . 'admin';
		$output = Director::forceSSL(array('/^admin/'), 'secure.mysite.com');
		$this->assertEquals($output, 'https://secure.mysite.com/admin');
	}

	/**
	 * @covers Director::extract_request_headers()
	 */
	public function testExtractRequestHeaders() {
		$request = array(
			'REDIRECT_STATUS'      => '200',
			'HTTP_HOST'            => 'host',
			'HTTP_USER_AGENT'      => 'User Agent',
			'HTTP_ACCEPT'          => 'text/html',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_COOKIE'          => 'PastMember=1',
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
			'Cookie'          => 'PastMember=1',
			'Content-Type'    => 'text/xml',
			'Content-Length'  => '10'
		);
		
		$this->assertEquals($headers, Director::extract_request_headers($request));
	}

	public function testUnmatchedRequestReturns404() {
		$this->assertEquals(404, Director::test('no-route')->getStatusCode());
	}

	public function testIsHttps() {
		// nothing available
		$headers = array(
			'HTTP_X_FORWARDED_PROTOCOL', 'HTTPS', 'SSL'
		);

		$origServer = $_SERVER;

		foreach($headers as $header) {
			if(isset($_SERVER[$header])) {
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
}

class DirectorTestRequest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array(
		'returnGetValue',
		'returnPostValue',
		'returnRequestValue',
		'returnCookieValue',
	);

	public function returnGetValue($request)		{ return $_GET['somekey']; }

	public function returnPostValue($request)		{ return $_POST['somekey']; }

	public function returnRequestValue($request)	{ return $_REQUEST['somekey']; }

	public function returnCookieValue($request)		{ return $_COOKIE['somekey']; }

}
