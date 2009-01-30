<?php

class RestfulServiceTest extends SapphireTest {
	function testGetData() {
		$connection = new RestfulService(Director::absoluteBaseURL());
		$test1params = array(
			'test1a' => 4352655636.76543, // number test
			'test1b' => '$&+,/:;=?@#"\'%', // special char test. These should all get encoded
			'test1c' => 'And now for a string test' // string test
		);
		$connection->setQueryString($test1params);
		$test1 = $connection->request('RestfulServiceTest_Controller?usetestmanifest=1&flush=1')->getBody();
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
		$test2suburl = 'RestfulServiceTest_Controller/?usetestmanifest=1&flush=1&';
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
		$connection = new RestfulService(Director::absoluteBaseURL(), 0);
		$test1params = array(
			'test1a' => mktime(),
			'test1b' => mt_rand(),
			'test1c' => 'And now for a string test'
		);
		$test1 = $connection->request('RestfulServiceTest_Controller/?usetestmanifest=1&flush=1', 'POST', $test1params)->getBody();
		foreach ($test1params as $key => $value) {
			$this->assertContains("<request_item name=\"$key\">$value</request_item>", $test1);
			$this->assertContains("<post_item name=\"$key\">$value</post_item>", $test1);
		}
	}
}

class RestfulServiceTest_Controller extends Controller {
	public function index() {
		ContentNegotiator::disable();
		BasicAuth::disable();
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
		header('Content-type: text/xml');
		echo $out;
	}
}

?>
