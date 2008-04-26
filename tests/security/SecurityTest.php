<?php

/**
 * Test the security class, including log-in form, change password form, etc
 */
class SecurityTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/security/MemberTest.yml';
	
	
	/**
	 * Test that the login form redirects to the change password form after logging in with an expired password
	 */
	function testExpiredPassword() {
		
		// BAD PASSWORDS ARE LOCKED OUT
		
		$session = new Session(array());
		$badResponse = Director::test('Security/login?executeForm=LoginForm', array(
			'Email' => 'sam@silverstripe.com', 
			'Password' => 'badpassword', 
			'AuthenticationMethod' => 'MemberAuthenticator',
			'action_dologin' => 1,
			'BackURL' => 'test/link'),
			$session
		);
		$this->assertEquals(302, $badResponse->getStatusCode());
		$this->assertRegExp('/Security\/login/', $badResponse->getHeader('Location'));
		$this->assertNull($session->inst_get('loggedInAs'));

		// UNEXPIRED PASSWORD GO THROUGH WITHOUT A HITCH

		$session = new Session(array());
		$goodResponse = Director::test('Security/login?executeForm=LoginForm', array(
			'Email' => 'sam@silverstripe.com', 
			'Password' => '1nitialPassword', 
			'AuthenticationMethod' => 'MemberAuthenticator',
			'action_dologin' => 1,
			'BackURL' => 'test/link'),
			$session
		);
		$this->assertEquals(302, $goodResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'test/link', $goodResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'test'), $session->inst_get('loggedInAs'));
		
		// EXPIRED PASSWORDS ARE SENT TO THE CHANGE PASSWORD FORM
		
		$session = new Session(array());
		$expiredResponse = Director::test('Security/login?executeForm=LoginForm', array(
			'Email' => 'expired@silverstripe.com', 
			'Password' => '1nitialPassword', 
			'AuthenticationMethod' => 'MemberAuthenticator',
			'action_dologin' => 1,
			'BackURL' => 'test/link'),
			$session
		);
		$this->assertEquals(302, $expiredResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'Security/changepassword', $expiredResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'expiredpassword'), $session->inst_get('loggedInAs'));
	}
	
}