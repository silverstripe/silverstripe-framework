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
 * @group sanitychecks
 * 
 * @package sapphire
 * @subpackage tests
 */
class WebserverRoutingTest extends SapphireTest {

	function testCanAccessWebserverThroughCurl() {
		if(!function_exists('curl_init')) return;
		
		$url = Director::absoluteBaseURL() . 'WebserverRoutingTest_Controller/?usetestmanifest=1&flush=1';
		
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL,$url );
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		$this->assertEquals(curl_error($ch), '');
		$this->assertTrue(in_array(trim($response), array('ok', _t('BasicAuth.ENTERINFO'))));
		
		curl_close($ch);
	}
	
}

/**
 * @package sapphire
 * @subpackage tests
 */
class WebserverRoutingTest_Controller extends Controller {
	function index() {
		BasicAuth::protect_entire_site(false);
		
		return "ok";
	}
}
?>