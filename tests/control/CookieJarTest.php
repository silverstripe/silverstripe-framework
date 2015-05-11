<?php

/**
 * Testing CookieJar
 *
 * Testing the CookieJar acts as expected and keeps track of Cookies it is loaded
 * with as well as new cookies that are set during the running of an application
 *
 */
class CookieJarTest extends SapphireTest {

	/**
	 * Test that the construction argument is stored and returned as expected
	 */
	public function testConstruct() {

		//some default cookies to load
		$defaultCookies = array(
			'cookie1' => 1,
			'cookie2' => 'cookies',
			'cookie3' => 'test',
		);

		$cookieJar = new CookieJar($defaultCookies);

		//make sure all the "recieved" cookies are as expected
		$this->assertEquals($defaultCookies, $cookieJar->getAll(false));

		//make sure there are no "phantom" cookies
		$this->assertEquals($defaultCookies, $cookieJar->getAll(true));

		//check an empty array is accepted
		$cookieJar = new CookieJar(array());
		$this->assertEmpty($cookieJar->getAll(false));


		//check no argument is accepted
		$cookieJar = new CookieJar();
		$this->assertEmpty($cookieJar->getAll(false));
	}

	/**
	 * Test that we can set and get cookies
	 */
	public function testSetAndGet() {
		$cookieJar = new CookieJar();

		$this->assertEmpty($cookieJar->get('testCookie'));

		//set a test cookie
		$cookieJar->set('testCookie', 'testVal');

		//make sure it was set
		$this->assertEquals('testVal', $cookieJar->get('testCookie'));

		//make sure we can distinguish it from ones that were "existing"
		$this->assertEmpty($cookieJar->get('testCookie', false));

		//PHP will replace an incoming COOKIE called 'var.with.dots' to 'var_with_dots'
		$cookieJar = new CookieJar(array(
			'var_with_dots' => 'value',
		));

		$cookieJar->set('test.dots', 'dots');

		//check we can access with '.' and with '_'
		$this->assertEquals('value', $cookieJar->get('var.with.dots'));
		$this->assertEquals('value', $cookieJar->get('var_with_dots'));
		$this->assertEquals('dots', $cookieJar->get('test.dots'));
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

		//set a new cookie
		$cookieJar->set('cookieNew', 'i am new');

		//check we can fetch new and old cookie values
		$this->assertEquals('i woz here', $cookieJar->get('cookieExisting'));
		$this->assertEquals('i woz here', $cookieJar->get('cookieExisting', false));
		$this->assertEquals('i am new', $cookieJar->get('cookieNew'));
		//there should be no original value for the new cookie
		$this->assertEmpty($cookieJar->get('cookieNew', false));

		//change the existing cookie, can we fetch the new and old value
		$cookieJar->set('cookieExisting', 'i woz changed');

		$this->assertEquals('i woz changed', $cookieJar->get('cookieExisting'));
		$this->assertEquals('i woz here', $cookieJar->get('cookieExisting', false));

		//check we can get all cookies
		$this->assertEquals(array(
			'cookieExisting' => 'i woz changed',
			'cookieNew' => 'i am new',
		), $cookieJar->getAll());

		//check we can get all original cookies
		$this->assertEquals(array(
			'cookieExisting' => 'i woz here',
		), $cookieJar->getAll(false));
	}

	/**
	 * Check we can remove cookies and we can access their original values
	 */
	public function testForceExpiry() {
		//load an existing cookie
		$cookieJar = new CookieJar(array(
			'cookieExisting' => 'i woz here',
		));

		//make sure it's available
		$this->assertEquals('i woz here', $cookieJar->get('cookieExisting'));

		//remove the cookie
		$cookieJar->forceExpiry('cookieExisting');

		//check it's gone
		$this->assertEmpty($cookieJar->get('cookieExisting'));

		//check we can get it's original value
		$this->assertEquals('i woz here', $cookieJar->get('cookieExisting', false));


		//check we can add a new cookie and remove it and it doesn't leave any phantom values
		$cookieJar->set('newCookie', 'i am new');

		//check it's set by not received
		$this->assertEquals('i am new', $cookieJar->get('newCookie'));
		$this->assertEmpty($cookieJar->get('newCookie', false));

		//remove it
		$cookieJar->forceExpiry('newCookie');

		//check it's neither set nor received
		$this->assertEmpty($cookieJar->get('newCookie'));
		$this->assertEmpty($cookieJar->get('newCookie', false));
	}

}
