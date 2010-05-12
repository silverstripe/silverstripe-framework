<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo test Director::alternateBaseFolder()
 */
class DirectorTest extends SapphireTest {
	
	function setUp() {
		parent::setUp();
		
		Director::addRules(99, array(
			'DirectorTestRule/$Action/$ID/$OtherID' => 'DirectorTestRequest_Controller'
		));
	}
	
	function tearDown() {
		// TODO Remove director rule, currently API doesnt allow this
		
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
		Director::setBaseURL('/relativebase/');
		$this->assertEquals('/relativebase/', Director::baseURL());
		$this->assertEquals(Director::protocolAndHost() . '/relativebase/', Director::absoluteBaseURL());
		$this->assertEquals(Director::protocolAndHost() . '/relativebase/subfolder/test', Director::absoluteURL('subfolder/test'));

		// absolute base URLs - you should end them in a /
		Director::setBaseURL('http://www.example.org/');
		$this->assertEquals('http://www.example.org/', Director::baseURL());
		$this->assertEquals('http://www.example.org/', Director::absoluteBaseURL());
		$this->assertEquals('http://www.example.org/subfolder/test', Director::absoluteURL('subfolder/test'));

		// Setting it to false restores functionality
		Director::setBaseURL(false);
		$this->assertEquals(BASE_URL.'/', Director::baseURL());
		$this->assertEquals(Director::protocolAndHost().BASE_URL.'/', Director::absoluteBaseURL(BASE_URL));
		$this->assertEquals(Director::protocolAndHost().BASE_URL . '/subfolder/test', Director::absoluteURL('subfolder/test'));
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
		$this->assertTrue(Director::is_absolute_url('http://test.com'));
		$this->assertTrue(Director::is_absolute_url('https://test.com'));
		$this->assertTrue(Director::is_absolute_url('   https://test.com/testpage   '));
		$this->assertFalse(Director::is_absolute_url('test.com/testpage'));
		$this->assertTrue(Director::is_absolute_url('ftp://test.com'));
		$this->assertFalse(Director::is_absolute_url('/relative'));
		$this->assertFalse(Director::is_absolute_url('relative'));
		$this->assertFalse(Director::is_absolute_url('/relative/?url=http://test.com'));
		$this->assertTrue(Director::is_absolute_url('http://test.com/?url=http://test.com'));
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
		$this->assertFalse(Director::is_relative_url('http://test.com/?url=' . $siteUrl));
	}
	
	public function testMakeRelative() {
		$siteUrl = Director::absoluteBaseURL();
		$siteUrlNoProtocol = preg_replace('/https?:\/\//', '', $siteUrl);
		$this->assertEquals(Director::makeRelative("$siteUrl"), '');
		//$this->assertEquals(Director::makeRelative("https://$siteUrlNoProtocol"), '');
		$this->assertEquals(Director::makeRelative("   $siteUrl/testpage   "), 'testpage');
		//$this->assertEquals(Director::makeRelative("$siteUrlNoProtocol/testpage"), 'testpage');
		$this->assertEquals(Director::makeRelative('ftp://test.com'), 'ftp://test.com');
		$this->assertEquals(Director::makeRelative('http://test.com'), 'http://test.com');
		// the below is not a relative URL, test makes no sense
		// $this->assertEquals(Director::makeRelative('/relative'), '/relative');
		$this->assertEquals(Director::makeRelative('relative'), 'relative');
		$this->assertEquals(Director::makeRelative("$siteUrl/?url=http://test.com"), '?url=http://test.com');
	}
	
	public function testIsSiteUrl() {
		$siteUrl = Director::absoluteBaseURL();
		$siteUrlNoProtocol = preg_replace('/https?:\/\//', '', $siteUrl);
		$this->assertTrue(Director::is_site_url($siteUrl));
		$this->assertTrue(Director::is_site_url("$siteUrl/testpage"));
		$this->assertTrue(Director::is_site_url("   $siteUrl/testpage   "));
		$this->assertTrue(Director::is_site_url("$siteUrlNoProtocol/testpage"));
		$this->assertFalse(Director::is_site_url('http://test.com/testpage'));
		//$this->assertFalse(Director::is_site_url('test.com/testpage'));
		$this->assertTrue(Director::is_site_url('/relative'));
		$this->assertTrue(Director::is_site_url('relative'));
		$this->assertFalse(Director::is_site_url("http://test.com/?url=$siteUrl"));
	}
	
	public function testResetGlobalsAfterTestRequest() {

		$_GET = array('somekey' => 'getvalue');
		$_POST = array('somekey' => 'postvalue');
		$_COOKIE = array('somekey' => 'cookievalue');

		$getresponse = Director::test('errorpage?somekey=sometestgetvalue', array('somekey' => 'sometestpostvalue'), null, null, null, null, array('somekey' => 'sometestcookievalue'));

		$this->assertEquals('getvalue', $_GET['somekey'], '$_GET reset to original value after Director::test()');
		$this->assertEquals('postvalue', $_POST['somekey'], '$_POST reset to original value after Director::test()');
		$this->assertEquals('cookievalue', $_COOKIE['somekey'], '$_COOKIE reset to original value after Director::test()');
	}
	
	public function testTestRequestCarriesGlobals() {

		$fixture = array('somekey' => 'sometestvalue');
	
		foreach(array('get', 'post') as $method) {

			foreach(array('return%sValue', 'returnRequestValue', 'returnCookieValue') as $testfunction) {

				$url = 'DirectorTestRequest_Controller/' . sprintf($testfunction, ucfirst($method)) . '?' . http_build_query($fixture);
				$getresponse = Director::test($url, $fixture, null, strtoupper($method), null, null, $fixture);

				$this->assertType('SS_HTTPResponse', $getresponse, 'Director::test() returns SS_HTTPResponse');
				$this->assertEquals($fixture['somekey'], $getresponse->getBody(), 'Director::test() ' . $testfunction);

			}

		}
	}
	
	function testURLParam() {
		Director::test('DirectorTestRule/myaction/myid/myotherid');
		// TODO Works on the assumption that urlParam() is not unset after a test run, which is dodgy
		$this->assertEquals(Director::urlParam('Action'), 'myaction');
		$this->assertEquals(Director::urlParam('ID'), 'myid');
		$this->assertEquals(Director::urlParam('OtherID'), 'myotherid');
	}
	
	function testURLParams() {
		Director::test('DirectorTestRule/myaction/myid/myotherid');
		// TODO Works on the assumption that urlParam() is not unset after a test run, which is dodgy
		$this->assertEquals(
			Director::urlParams(), 
			array(
				'Controller' => 'DirectorTestRequest_Controller',
				'Action' => 'myaction', 
				'ID' => 'myid', 
				'OtherID' => 'myotherid'
			)
		);
	}
	
}

class DirectorTestRequest_Controller extends Controller implements TestOnly {

	public function returnGetValue($request)		{ return $_GET['somekey']; }

	public function returnPostValue($request)		{ return $_POST['somekey']; }

	public function returnRequestValue($request)	{ return $_REQUEST['somekey']; }

	public function returnCookieValue($request)		{ return $_COOKIE['somekey']; }

}

?>