<?php

class RestfulServiceTest extends SapphireTest {
	
	function testSpecialCharacters() {
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
	
	function testGetDataWithSetQueryString() {
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
	
	function testGetDataWithUrlParameters() {
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
	
	function testPostData() {
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

	function testPutData() {
		$service = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL(), 0);
		$data = 'testPutData';
		$responseBody = $service->request('RestfulServiceTest_Controller/', 'PUT', $data)->getBody();
		$this->assertContains("<body>$data</body>", $responseBody);
	}
	
	function testConnectionDoesntCacheWithDifferentUrl() {
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
	function testIncorrectData() {
		$connection = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL(), 0);
		$test1 = $connection->request('RestfulServiceTest_Controller/invalid');
		$test1->xpath("\\fail");
	}
	
	function testHttpErrorWithoutCache() {
		$connection = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL(), 0);
		$response = $connection->request('RestfulServiceTest_Controller/httpErrorWithoutCache');
		
		$this->assertEquals(400, $response->getStatusCode());
		$this->assertFalse($response->getCachedBody());
		$this->assertContains("<error>HTTP Error</error>", $response->getBody());
		
	}
	
}

class RestfulServiceTest_Controller extends Controller {
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
		$this->response->addHeader('Content-type', 'text/xml');
		
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
		$this->response->addHeader('Content-type', 'text/xml');
		
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
 * @package sapphire
 * @subpackage tests
 */
class RestfulServiceTest_MockRestfulService extends RestfulService {
	
	public $session = null;

	public function request($subURL = '', $method = "GET", $data = null, $headers = null) {
		
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

		// Custom for mock implementation: Use Director::test()
		$body = null;
		$postVars = null;
		
		if($method!='POST') $body = $data;
		else $postVars = $data;
		
		$responseFromDirector = Director::test($url, $postVars, $this->session, $method, $body, $headers);
		$response = new RestfulService_Response(
			$responseFromDirector->getBody(), 
			$responseFromDirector->getStatusCode()
		);

		return $response;
	}
}