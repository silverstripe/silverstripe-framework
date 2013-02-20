<?php

/**
 * @package framework
 * @subpackage tests
 */
class RestfulServiceTest extends SapphireTest {
	
	protected $member_unique_identifier_field = ''; 
	
	public function setUp() {
		// backup the project unique identifier field
		$this->member_unique_identifier_field = Member::get_unique_identifier_field();

		Member::set_unique_identifier_field('Email');

		parent::setUp(); 
	} 

	public function tearDown() {
		parent::tearDown(); 

		// set old member::get_unique_identifier_field value 
		if ($this->member_unique_identifier_field) { 
			Member::set_unique_identifier_field($this->member_unique_identifier_field); 
		}
	} 

	public function testSpecialCharacters() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$url = 'RestfulServiceTest_Controller/';
		$params = array(
			'test1a' => 4352655636.76543, // number test
			'test1b' => '$&+,/:;=?@#%', // special char test. These should all get encoded
			'test1c' => 'And now for a string test' // string test
		);
		$service->setQueryString($params);
		$responseBody = $service->request($url)->getBody();
		foreach ($params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $responseBody);
			$this->assertContains("<get_item name=\"$key\">$value</get_item>", $responseBody);
		}
	}
	
	public function testGetDataWithSetQueryString() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$url = 'RestfulServiceTest_Controller/';
		$params = array(
			'test1a' => 'val1a',
			'test1b' => 'val1b'
		);
		$service->setQueryString($params);
		$responseBody = $service->request($url)->getBody();
		foreach ($params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $responseBody);
			$this->assertContains("<get_item name=\"$key\">$value</get_item>", $responseBody);
		}
	}
	
	public function testGetDataWithUrlParameters() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$url = 'RestfulServiceTest_Controller/';
		$params = array(
			'test1a' => 'val1a',
			'test1b' => 'val1b'
		);
		$url .= '?' . http_build_query($params);
		$responseBody = $service->request($url)->getBody();
		foreach ($params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $responseBody);
			$this->assertContains("<get_item name=\"$key\">$value</get_item>", $responseBody);
		}
	}
	
	public function testPostData() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL(), 0);
		$params = array(
			'test1a' => 'val1a',
			'test1b' => 'val1b'
		);
		$responseBody = $service->request('RestfulServiceTest_Controller/', 'POST', $params)->getBody();
		foreach ($params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $responseBody);
			$this->assertContains("<post_item name=\"$key\">$value</post_item>", $responseBody);
		}
	}

	public function testPutData() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL(), 0);
		$data = 'testPutData';
		$responseBody = $service->request('RestfulServiceTest_Controller/', 'PUT', $data)->getBody();
		$this->assertContains("<body>$data</body>", $responseBody);
	}
	
	public function testConnectionDoesntCacheWithDifferentUrl() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$url = 'RestfulServiceTest_Controller/';
		
		// First run
		$params = array(
			'test1a' => 'first run',
		);
		$service->setQueryString($params);
		$responseBody = $service->request($url)->getBody();
		$this->assertContains("<request_item name=\"test1a\">first run</request_item>", $responseBody);
		
		// Second run
		$params = array(
			'test1a' => 'second run',
		);
		$service->setQueryString($params);
		$responseBody = $service->request($url)->getBody();
		$this->assertContains("<request_item name=\"test1a\">second run</request_item>", $responseBody);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error 
	 */
	public function testIncorrectData() {
		$connection = new RestfulService(Director::absoluteBaseURL(), 0);
		$test1 = $connection->request('RestfulServiceTest_Controller/invalid');
		$test1->xpath("\\fail");
	}
	
	public function testHttpErrorWithoutCache() {
		$connection = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL(), 0);
		$response = $connection->request('RestfulServiceTest_Controller/httpErrorWithoutCache');

		$this->assertEquals(400, $response->getStatusCode());
		$this->assertFalse($response->getCachedBody());
		$this->assertContains("<error>HTTP Error</error>", $response->getBody());
		
	}
	
	public function testHttpErrorWithCache() {
		$subUrl = 'RestfulServiceTest_Controller/httpErrorWithCache';
		$connection = new RestfulServiceTest_MockErrorService(Director::absoluteBaseURL(), 0);
		$this->createFakeCachedResponse($connection, $subUrl); 
		$response = $connection->request($subUrl);
		$this->assertEquals(400, $response->getStatusCode());
		$this->assertEquals("Cache response body",$response->getCachedBody());
		$this->assertContains("<error>HTTP Error</error>", $response->getBody());
		
	}
	
	/**
	 * Simulate cached response file for testing error requests that are supposed to have cache files
	 *
	 * @todo Generate the cachepath without hardcoding the cache data
	 */
	private function createFakeCachedResponse($connection, $subUrl) {
		$fullUrl = $connection->getAbsoluteRequestURL($subUrl);
		//these are the defaul values that one would expect in the
		$basicAuthStringMethod = new ReflectionMethod('RestfulServiceTest_MockErrorService', 'getBasicAuthString');
		$basicAuthStringMethod->setAccessible(true);
		$cachePathMethod = new ReflectionMethod('RestfulServiceTest_MockErrorService', 'getCachePath');
		$cachePathMethod->setAccessible(true);
		$cache_path = $cachePathMethod->invokeArgs($connection, array(array(
			$fullUrl,
			'GET',
			null,
			array(),
			array(),
			$basicAuthStringMethod->invoke($connection)
		)));

		$cacheResponse = new RestfulService_Response("Cache response body");
		$store = serialize($cacheResponse);
		file_put_contents($cache_path, $store);
	}

	public function testHttpHeaderParseing() {
		$headers = "content-type: text/html; charset=UTF-8\r\n".
					"Server: Funky/1.0\r\n".
					"Set-Cookie: foo=bar\r\n".
					"Set-Cookie: baz=quux\r\n".
					"Set-Cookie: bar=foo\r\n";
		$expected = array(
			'Content-Type' => 'text/html; charset=UTF-8',
			'Server' => 'Funky/1.0',
			'Set-Cookie' => array(
				'foo=bar',
				'baz=quux',
				'bar=foo'
			)
		);
		$headerFunction = new ReflectionMethod('RestfulService', 'parseRawHeaders');
		$headerFunction->setAccessible(true);
		$this->assertEquals(
			$expected,
			$headerFunction->invoke(
				new RestfulService(Director::absoluteBaseURL(),0), $headers
			)
		);
	}

}

class RestfulServiceTest_Controller extends Controller implements TestOnly {

	public static $allowed_actions = array(
		'index',
		'httpErrorWithoutCache',
		'httpErrorWithCache'
	);

	public function init() {
		$this->basicAuthEnabled = false;

		parent::init();
	}

	public function index() {
		$request = '';
		foreach ($this->request->requestVars() as $key=>$value) {
			$request .= "\t\t<request_item name=\"$key\">$value</request_item>\n";
		}
		$get = '';
		foreach ($this->request->getVars() as $key => $value) {
			$get .= "\t\t<get_item name=\"$key\">$value</get_item>\n";
		}
		$post = '';
		foreach ($this->request->postVars() as $key => $value) {
			$post .= "\t\t<post_item name=\"$key\">$value</post_item>\n";
		}
		$body = $this->request->getBody();
		
		$out = <<<XML
<?xml version="1.0"?>
<test>
	<request>$request</request>
	<get>$get</get>
	<post>$post</post>
	<body>$body</body>
</test>
XML;
		$this->response->setBody($out);
		$this->response->setHeader('Content-type', 'text/xml');
		
		return $this->response;
	}
	
	public function invalid() {
		$out = <<<XML
<?xml version="1.0"?>
<test>
	<fail><invalid>
</test>
XML;
		header('Content-type: text/xml');
		echo $out;		
	}
	
	public function httpErrorWithoutCache() {
		$out = <<<XML
<?xml version="1.0"?>
<test>
	<error>HTTP Error</error>
</test>
XML;
		
		$this->response->setBody($out);
		$this->response->setStatusCode(400); 
		$this->response->setHeader('Content-type', 'text/xml');

		return $this->response;
	}
	
	/**
	 * The body of this method is the same as self::httpErrorWithoutCache()
	 * but we need it for caching since caching using request url to determine path to cache file
	 */
	public function httpErrorWithCache() {
		return $this->httpErrorWithoutCache();
	}
}

/**
 * Mock implementation of {@link RestfulService}, which uses {@link Director::test()}
 * instead of direct curl system calls.
 * 
 * @todo Less overloading of request()
 * @todo Currently only works with relative (internal) URLs
 * 
 * @package framework
 * @subpackage tests
 */
class RestfulServiceTest_MockRestfulService extends RestfulService {
	
	public $session = null;
	
	public function request($subURL = '', $method = "GET", $data = null, $headers = null, $curlOptions = array()) {
		
		if(!$this->session) {
			$this->session = new Session(array());
		}
		
		$url = $this->baseURL . $subURL; // Url for the request

		if($this->queryString) {
			if(strpos($url, '?') !== false) {
				$url .= '&' . $this->queryString;
			} else {
				$url .= '?' . $this->queryString;
			}
		}
		$url = str_replace(' ', '%20', $url); // Encode spaces
	
		// Custom for mock implementation: Director::test() doesn't cope with absolute URLs
		$url = Director::makeRelative($url);
		
		$method = strtoupper($method);
		
		assert(in_array($method, array('GET','POST','PUT','DELETE','HEAD','OPTIONS')));
		
		// Add headers
		if($this->customHeaders) {
			$headers = array_merge((array)$this->customHeaders, (array)$headers);
		}
	
		// Add authentication
		if($this->authUsername) {
			$headers[] = "Authorization: Basic " . base64_encode(
				$this->authUsername.':'.$this->authPassword
			);
		}

		// Extract the GET params.
		$get = array();

		if(strpos($url, '?') !== false) {
			list($url, $raw) = explode('?', $url, 2);
			parse_str($raw, $get);
		}

		// Perform the request.
		if($method == 'POST') {
			$request = new SS_HTTPRequest('POST', $url, $get, $data);
		} else {
			$request = new SS_HTTPRequest('GET', $url, $get);
			$request->setBody($data);
		}

		if($headers) {
			$request->setHeaders($headers);
		}

		$response = Director::test($request, $this->session);

		return new RestfulService_Response(
			$response->getBody(), $response->getStatusCode()
		);
	}
}

/**
 * A mock service that returns a 400 error for requests.
 */
class RestfulServiceTest_MockErrorService extends RestfulService {

	public function curlRequest($url, $method, $data = null, $headers = null, $curlOptions = array()) {
		return new RestfulService_Response('<error>HTTP Error</error>', 400);
	}

}
