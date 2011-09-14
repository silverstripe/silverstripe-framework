<?php
/**
 * Test that SilverStripe is accessible through the webserver
 * by using curl with an actual HTTP request, instead of an in-memory
 * test through {@link Director::test()}.
 * This can help to uncover e.g. webserver routing problems with .htaccess files.
 * 
 * @todo Exclude this test from a standard test run - not all test environments
 *  might have a webserver installed, or have it accessible for HTTP requests
 *  from localhost.
 * 
 * @package sapphire
 * @subpackage tests
 */
class WebserverRoutingTest extends SapphireTest {

	function testCanAccessWebserverThroughCurl() {
		if(!function_exists('curl_init')) return;
		
		$url = Director::absoluteBaseURL() . 'Security/ping';
		
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL,$url );
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		$this->assertEquals(curl_error($ch), '');
		$this->assertEquals('1', $response);
		
		curl_close($ch);
	}
	
}
