<?php
/**
 * @package framework
 * @subpackage tests
 */

class BasicAuthTest extends FunctionalTest {

	static $original_unique_identifier_field;

	static $fixture_file = 'BasicAuthTest.yml';

	public function setUp() {
		parent::setUp();

		// Fixtures assume Email is the field used to identify the log in identity
		self::$original_unique_identifier_field = Member::get_unique_identifier_field();
		Member::set_unique_identifier_field('Email');
	}

	public function tearDown() {
		parent::tearDown();
		
		BasicAuth::protect_entire_site(false);
		Member::set_unique_identifier_field(self::$original_unique_identifier_field);
	}

	public function testBasicAuthEnabledWithoutLogin() {
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode());
	}
	
	public function testBasicAuthDoesntCallActionOrFurtherInitOnAuthFailure() {
		Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertFalse(BasicAuthTest_ControllerSecuredWithPermission::$index_called);
		$this->assertFalse(BasicAuthTest_ControllerSecuredWithPermission::$post_init_called);

		Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithPermission', 'user-in-mygroup@test.com', 'test'
		));
		$this->assertTrue(BasicAuthTest_ControllerSecuredWithPermission::$index_called);
		$this->assertTrue(BasicAuthTest_ControllerSecuredWithPermission::$post_init_called);
	}

	public function testBasicAuthEnabledWithPermission() {
		$response = Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithPermission', 'user-in-mygroup@test.com', 'wrongpassword'
		));
		$this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');

		$response = Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithPermission', 'user-without-groups@test.com', 'test'
		));
		$this->assertEquals(401, $response->getStatusCode(), 'Valid user without required permission has no access');

		$response = Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithPermission', 'user-in-mygroup@test.com', 'test'
		));
		$this->assertEquals(200, $response->getStatusCode(), 'Valid user with required permission has access');
	}
	
	public function testBasicAuthEnabledWithoutPermission() {
		$response = Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithoutPermission', 'user-without-groups@test.com', 'wrongpassword'
		));
		$this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');

		$response = Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithoutPermission', 'user-without-groups@test.com', 'test'
		));
		$this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');

		$response = Director::test($this->getBasicAuthRequest(
			'BasicAuthTest_ControllerSecuredWithoutPermission', 'user-in-mygroup@test.com', 'test'
		));
		$this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');
	}

	private function getBasicAuthRequest($url, $username, $password) {
		return new SS_HTTPRequest('GET', $url, null, array(
			'server' => array('PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => $password)
		));
	}

}

class BasicAuthTest_ControllerSecuredWithPermission extends Controller implements TestOnly {
	
	static $post_init_called = false;
	
	static $index_called = false;

	protected $template = 'BlankPage';
	
	public function init() {
		self::$post_init_called = false;
		self::$index_called = false;
		
		BasicAuth::protect_entire_site(true, 'MYCODE');
		parent::init();
		
		self::$post_init_called = true;
	}
	
	public function index() {
		self::$index_called = true;
	}



}

class BasicAuthTest_ControllerSecuredWithoutPermission extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	public function init() {
		BasicAuth::protect_entire_site(true, null);
		parent::init();
	}
	
}
