<?php
/**
 * @package framework
 * @subpackage tests
 */

class BasicAuthTest extends FunctionalTest {

	static $original_unique_identifier_field;

	protected static $fixture_file = 'BasicAuthTest.yml';

	public function setUp() {
		parent::setUp();

		// Fixtures assume Email is the field used to identify the log in identity
		Member::config()->unique_identifier_field = 'Email';
		Security::$force_database_is_ready = true; // Prevents Member test subclasses breaking ready test
		Member::config()->lock_out_after_incorrect_logins = 10;
	}

	public function testBasicAuthEnabledWithoutLogin() {
		$origUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
		$origPw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);

		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode());

		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}

	public function testBasicAuthDoesntCallActionOrFurtherInitOnAuthFailure() {
		$origUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
		$origPw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertFalse(BasicAuthTest_ControllerSecuredWithPermission::$index_called);
		$this->assertFalse(BasicAuthTest_ControllerSecuredWithPermission::$post_init_called);

		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertTrue(BasicAuthTest_ControllerSecuredWithPermission::$index_called);
		$this->assertTrue(BasicAuthTest_ControllerSecuredWithPermission::$post_init_called);

		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}

	public function testBasicAuthEnabledWithPermission() {
		$origUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
		$origPw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'wrongpassword';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');

		$_SERVER['PHP_AUTH_USER'] = 'user-without-groups@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(401, $response->getStatusCode(), 'Valid user without required permission has no access');

		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
		$this->assertEquals(200, $response->getStatusCode(), 'Valid user with required permission has access');

		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}

	public function testBasicAuthEnabledWithoutPermission() {
		$origUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
		$origPw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

		$_SERVER['PHP_AUTH_USER'] = 'user-without-groups@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'wrongpassword';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');

		$_SERVER['PHP_AUTH_USER'] = 'user-without-groups@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');

		$_SERVER['PHP_AUTH_USER'] = 'user-in-mygroup@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');

		$_SERVER['PHP_AUTH_USER'] = $origUser;
		$_SERVER['PHP_AUTH_PW'] = $origPw;
	}

	public function testBasicAuthFailureIncreasesFailedLoginCount() {
		// Prior to login
		$check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
		$this->assertEquals(0, $check->FailedLoginCount);

		// First failed attempt
		$_SERVER['PHP_AUTH_USER'] = 'failedlogin@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'test';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
		$this->assertEquals(1, $check->FailedLoginCount);

		// Second failed attempt
		$_SERVER['PHP_AUTH_PW'] = 'testwrong';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
		$this->assertEquals(2, $check->FailedLoginCount);

		// successful basic auth should reset failed login count
		$_SERVER['PHP_AUTH_PW'] = 'Password';
		$response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission');
		$check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
		$this->assertEquals(0, $check->FailedLoginCount);
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
