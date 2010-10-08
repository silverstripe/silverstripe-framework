<?php

class RestfulServiceTest extends SapphireTest {
	
	function testGetData() {
		$connection = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$test1url = 'RestfulServiceTest_Controller/';
		$test1params = array(
			'test1a' => 4352655636.76543, // number test
			'test1b' => '$&+,/:;=?@#"\'%', // special char test. These should all get encoded
			'test1c' => 'And now for a string test' // string test
		);
		$connection->setQueryString($test1params);
		$test1 = $connection->request($test1url)->getBody();
		foreach ($test1params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $test1);
			$this->assertContains("<get_item name=\"$key\">$value</get_item>", $test1);
		}
		
		$connection->setQueryString(array());
		$test2params = array(
			'test2a' => 767545678.76887, // number test
			'test2b' => '%\'"@?=;:/,$', // special character checks
			'test2c' => 'And now for a string test', // string test
		);
		$test2suburl = 'RestfulServiceTest_Controller/?';
		foreach ($test2params as $key=>$value) {
			$test2suburl .= "$key=$value&";
		}
		
		$test2suburl = substr($test2suburl, 0, -1);
		$test2 = $connection->request($test2suburl)->getBody();
		foreach ($test2params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $test2);
			$this->assertContains("<get_item name=\"$key\">$value</get_item>", $test2);
		}
		
		$test3params = array_merge($test1params, $test2params); // We want to check using setQueryString() and hard coded
		$connection->setQueryString($test1params);
		$test3 = $connection->request($test2suburl)->getBody();
		foreach ($test3params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $test3);
			$this->assertContains("<get_item name=\"$key\">$value</get_item>", $test3);
		}
	}
	
	function testPostData() {
		$connection = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$url = 'RestfulServiceTest_Controller/';
		$test1params = array(
			'test1a' => mktime(),
			'test1b' => mt_rand(),
			'test1c' => 'And now for a string test'
		);
		$test1 = $connection->request($url, 'POST', $test1params)->getBody();
		foreach ($test1params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $test1);
			$this->assertContains("<post_item name=\"$key\">$value</post_item>", $test1);
		}
	}
	/**
	 * @expectedException PHPUnit_Framework_Error 
	 */
	function testIncorrectData() {
		$connection = new RestfulServiceTest_MockRestfulService(Director::absoluteBaseURL());
		$url = 'RestfulServiceTest_Controller/';
		$test1 = $connection->request($url);
		$test1->xpath("\\fail");
	}
}

class RestfulServiceTest_Controller extends Controller {
	public function index() {
		ContentNegotiator::disable();
		BasicAuth::protect_entire_site(false);
		$request_count = count($_REQUEST);
		$get_count = count($_GET);
		$post_count = count($_POST);
		$request = '';
		foreach ($_REQUEST as $key=>$value) {
			$request .= "\t\t<request_item name=\"$key\">$value</request_item>\n";
		}
		$get = '';
		foreach ($_GET as $key => $value) {
			$get .= "\t\t<get_item name=\"$key\">$value</get_item>\n";
		}
		$post = '';
		foreach ($_POST as $key => $value) {
			$post .= "\t\t<post_item name=\"$key\">$value</post_item>\n";
		}
		$out = <<<XML
<?xml version="1.0"?>
<test>
	<request count="$request_count">
$request	</request>
	<get count="$get_count">
$get	</get>
	<post count="$post_count">
$post	</post>
</test>
XML;
		
		$this->response->setBody($out);
		$this->response->addHeader('Content-type', 'text/xml');
		
		return $this->response;
	}
	
	public function invalid() {
		ContentNegotiator::disable();
		BasicAuth::protect_entire_site(false);
		$out = <<<XML
<?xml version="1.0"?>
<test>
	<fail><invalid>
</test>
XML;
		header('Content-type: text/xml');
		echo $out;		
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