<?php

class CookieTest extends SapphireTest {

	private $cookieInst;

	public function setUpOnce() {
		//store the cookie_backend so we can restore it after the tests
		$this->cookieInst = Cookie::get_inst();
		parent::setUpOnce();
	}

	public function tearDownOnce() {
		parent::tearDownOnce();
		//restore the cookie_backend
		Cookie::set_inst($this->cookieInst);
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
		);

		Cookie::clear_inst();

		$this->assertEquals($_COOKIE['cookie1'], Cookie::get('cookie1'));
		$this->assertEquals($_COOKIE['cookie2'], Cookie::get('cookie2'));
		$this->assertEquals($_COOKIE['cookie3'], Cookie::get('cookie3'));

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

		Cookie::clear_inst();

		$this->assertEmpty(Cookie::get('test'));

	}

	/**
	 * Check we can actually get the backend inst out
	 */
	public function testGetInst() {

		$inst = new CookieJar(array('test' => 'testvalue'));

		Cookie::set_inst($inst);

		$this->assertEquals($inst, Cookie::get_inst());

		$this->assertEquals('testvalue', Cookie::get('test'));

	}

}
