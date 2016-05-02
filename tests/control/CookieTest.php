<?php

class CookieTest extends SapphireTest {

	private $cookieInst;

	public function setUp() {
		parent::setUp();
		Injector::nest();
		Injector::inst()->registerService(new CookieJar($_COOKIE), 'Cookie_Backend');
	}

	public function tearDown() {
		//restore the cookie_backend
		Injector::unnest();
		parent::tearDown();
	}

	/**
	 * Check a new cookie inst will be loaded with the superglobal by default
	 */
	public function testCheckNewInstTakesSuperglobal() {
		//store the superglobal state
		$existingCookies = $_COOKIE;

		//set a mock state for the superglobal
		$_COOKIE = array(
			'cookie1' => 1,
			'cookie2' => 'cookies',
			'cookie3' => 'test',
			'cookie_4' => 'value',
		);

		Injector::inst()->unregisterNamedObject('Cookie_Backend');

		$this->assertEquals($_COOKIE['cookie1'], Cookie::get('cookie1'));
		$this->assertEquals($_COOKIE['cookie2'], Cookie::get('cookie2'));
		$this->assertEquals($_COOKIE['cookie3'], Cookie::get('cookie3'));
		$this->assertEquals($_COOKIE['cookie_4'], Cookie::get('cookie.4'));
		$this->assertEquals($_COOKIE['cookie_4'], Cookie::get('cookie_4'));

		//for good measure check the CookieJar hasn't stored anything extra
		$this->assertEquals($_COOKIE, Cookie::get_inst()->getAll(false));

		//restore the superglobal state
		$_COOKIE = $existingCookies;
	}

	/**
	 * Check we don't mess with super globals when manipulating cookies
	 *
	 * State should be managed sperately to the super global
	 */
	public function testCheckSuperglobalsArentTouched() {

		//store the current state
		$before = $_COOKIE;

		//change some cookies
		Cookie::set('cookie', 'not me');
		Cookie::force_expiry('cookie2');

		//assert it hasn't changed
		$this->assertEquals($before, $_COOKIE);

	}

	/**
	 * Check we can actually change a backend
	 */
	public function testChangeBackend() {

		Cookie::set('test', 'testvalue');

		$this->assertEquals('testvalue', Cookie::get('test'));

		Injector::inst()->registerService(new CookieJar(array()), 'Cookie_Backend');

		$this->assertEmpty(Cookie::get('test'));

	}

	/**
	 * Check we can actually get the backend inst out
	 */
	public function testGetInst() {

		$inst = new CookieJar(array('test' => 'testvalue'));

		Injector::inst()->registerService($inst, 'Cookie_Backend');

		$this->assertEquals($inst, Cookie::get_inst());

		$this->assertEquals('testvalue', Cookie::get('test'));

	}

	/**
	 * Test that we can set and get cookies
	 */
	public function testSetAndGet() {
		$this->assertEmpty(Cookie::get('testCookie'));

		//set a test cookie
		Cookie::set('testCookie', 'testVal');

		//make sure it was set
		$this->assertEquals('testVal', Cookie::get('testCookie'));

		//make sure we can distinguise it from ones that were "existing"
		$this->assertEmpty(Cookie::get('testCookie', false));
	}

	/**
	 * Test that we can distinguish between vars that were loaded on instantiation
	 * and those added later
	 */
	public function testExistingVersusNew() {
		//load with a cookie
		$cookieJar = new CookieJar(array(
			'cookieExisting' => 'i woz here',
		));
		Injector::inst()->registerService($cookieJar, 'Cookie_Backend');

		//set a new cookie
		Cookie::set('cookieNew', 'i am new');

		//check we can fetch new and old cookie values
		$this->assertEquals('i woz here', Cookie::get('cookieExisting'));
		$this->assertEquals('i woz here', Cookie::get('cookieExisting', false));
		$this->assertEquals('i am new', Cookie::get('cookieNew'));
		//there should be no original value for the new cookie
		$this->assertEmpty(Cookie::get('cookieNew', false));

		//change the existing cookie, can we fetch the new and old value
		Cookie::set('cookieExisting', 'i woz changed');

		$this->assertEquals('i woz changed', Cookie::get('cookieExisting'));
		$this->assertEquals('i woz here', Cookie::get('cookieExisting', false));

		//check we can get all cookies
		$this->assertEquals(array(
			'cookieExisting' => 'i woz changed',
			'cookieNew' => 'i am new',
		), Cookie::get_all());

		//check we can get all original cookies
		$this->assertEquals(array(
			'cookieExisting' => 'i woz here',
		), Cookie::get_all(false));
	}

	/**
	 * Check we can remove cookies and we can access their original values
	 */
	public function testForceExpiry() {
		//load an existing cookie
		$cookieJar = new CookieJar(array(
			'cookieExisting' => 'i woz here',
		));
		Injector::inst()->registerService($cookieJar, 'Cookie_Backend');

		//make sure it's available
		$this->assertEquals('i woz here', Cookie::get('cookieExisting'));

		//remove the cookie
		Cookie::force_expiry('cookieExisting');

		//check it's gone
		$this->assertEmpty(Cookie::get('cookieExisting'));

		//check we can get it's original value
		$this->assertEquals('i woz here', Cookie::get('cookieExisting', false));


		//check we can add a new cookie and remove it and it doesn't leave any phantom values
		Cookie::set('newCookie', 'i am new');

		//check it's set by not recieved
		$this->assertEquals('i am new', Cookie::get('newCookie'));
		$this->assertEmpty(Cookie::get('newCookie', false));

		//remove it
		Cookie::force_expiry('newCookie');

		//check it's neither set nor reveived
		$this->assertEmpty(Cookie::get('newCookie'));
		$this->assertEmpty(Cookie::get('newCookie', false));
	}

}
