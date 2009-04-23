<?php
/**
 * Test the security class, including log-in form, change password form, etc
 *
 * @package sapphire
 * @subpackage tests
 */
class SecurityTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/security/MemberTest.yml';
	
	protected $autoFollowRedirection = false;
	
	protected $priorAuthenticators = array();
	
	protected $priorDefaultAuthenticator = null;
	
	function setUp() {
		// This test assumes that MemberAuthenticator is present and the default
		$this->priorAuthenticators = Authenticator::get_authenticators();
		$this->priorDefaultAuthenticator = Authenticator::get_default_authenticator();
		
		Authenticator::register('MemberAuthenticator');
		Authenticator::set_default_authenticator('MemberAuthenticator');
		
		parent::setUp();
	}
	
	function tearDown() {
		// Restore selected authenticator
		
		// MemberAuthenticator might not actually be present
		if(!in_array('MemberAuthenticator', $this->priorAuthenticators)) {
			Authenticator::unregister('MemberAuthenticator');
		}
		Authenticator::set_default_authenticator($this->priorDefaultAuthenticator);
		
		parent::tearDown();
	}
	
	function testLogInAsSomeoneElse() {
		$member = DataObject::get_one('Member');

		/* Log in with any user that we can find */
		$this->session()->inst_set('loggedInAs', $member->ID);

		/* View the Security/login page */
		$this->get('Security/login');
		
		$items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm input.action');
		
		/* We have only 1 input, one to allow the user to log in as someone else */
		$this->assertEquals(count($items), 1, 'There is 1 input, allowing the user to log in as someone else.');

		$this->autoFollowRedirection = true;
		
		/* Submit the form, using only the logout action and a hidden field for the authenticator */
		$response = $this->submitForm(
			'MemberLoginForm_LoginForm', 
			null,
			array(
				'AuthenticationMethod' => 'MemberAuthenticator',
				'action_dologout' => 1,
			)
		);

		/* We get a good response */
		$this->assertEquals($response->getStatusCode(), 200, 'We have a 200 OK response');
		$this->assertNotNull($response->getBody(), 'There is body content on the page');

		/* Log the user out */
		$this->session()->inst_set('loggedInAs', null);
	}
	
	function testMemberIDInSessionDoesntExistInDatabaseHasToLogin() {
		/* Log in with a Member ID that doesn't exist in the DB */
		$this->session()->inst_set('loggedInAs', 500);

		$this->autoFollowRedirection = true;
		
		/* Attempt to get into the admin section */
		$this->get('admin');
		
		$items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm input.text');

		/* We have 2 text inputs - one for email, and another for the password */
		$this->assertEquals(count($items), 2, 'There are 2 inputs - one for email, another for password');

		$this->autoFollowRedirection = false;
		
		/* Log the user out */
		$this->session()->inst_set('loggedInAs', null);
	}
	
	function testExternalBackUrlRedirectionDisallowed() {
		// Test internal relative redirect
		$response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', 'testpage');
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertRegExp('/testpage/', $response->getHeader('Location'),
			"Internal relative BackURLs work when passed through to login form"
		);
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);
		
		// Test internal absolute redirect
		$response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', Director::absoluteBaseURL() . 'testpage');
		// for some reason the redirect happens to a relative URL
		$this->assertRegExp('/^' . preg_quote(Director::absoluteBaseURL(), '/') . 'testpage/', $response->getHeader('Location'),
			"Internal absolute BackURLs work when passed through to login form"
		);
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);
		
		// Test external redirect
		$response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', 'http://myspoofedhost.com');
		$this->assertNotRegExp('/^' . preg_quote('http://myspoofedhost.com', '/') . '/', $response->getHeader('Location'),
			"Redirection to external links in login form BackURL gets prevented as a measure against spoofing attacks"
		);
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);
	}
	
	/**
	 * Test that the login form redirects to the change password form after logging in with an expired password
	 */
	function testExpiredPassword() {
		/* BAD PASSWORDS ARE LOCKED OUT */
		$badResponse = $this->doTestLoginForm('sam@silverstripe.com' , 'badpassword');
		$this->assertEquals(302, $badResponse->getStatusCode());
		$this->assertRegExp('/Security\/login/', $badResponse->getHeader('Location'));
		$this->assertNull($this->session()->inst_get('loggedInAs'));

		/* UNEXPIRED PASSWORD GO THROUGH WITHOUT A HITCH */
		$goodResponse = $this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertEquals(302, $goodResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'test/link', $goodResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'test'), $this->session()->inst_get('loggedInAs'));
		
		/* EXPIRED PASSWORDS ARE SENT TO THE CHANGE PASSWORD FORM */
		$expiredResponse = $this->doTestLoginForm('expired@silverstripe.com' , '1nitialPassword');
		$this->assertEquals(302, $expiredResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'Security/changepassword', $expiredResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'expiredpassword'), $this->session()->inst_get('loggedInAs'));
	}
	
	function testRepeatedLoginAttemptsLockingPeopleOut() {
		Member::lock_out_after_incorrect_logins(5);
		
		/* LOG IN WITH A BAD PASSWORD 7 TIMES */

		for($i=1;$i<=7;$i++) {
			$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
			$member = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
			
			/* THE FIRST 4 TIMES, THE MEMBER SHOULDN'T BE LOCKED OUT */
			if($i < 5) {
				$this->assertNull($member->LockedOutUntil);
				$this->assertTrue(false !== stripos($this->loginErrorMessage(), _t('Member.ERRORWRONGCRED')));
			}
			
			/* AFTER THAT THE USER IS LOCKED OUT FOR 15 MINUTES */

			//(we check for at least 14 minutes because we don't want a slow running test to report a failure.)
			else {
				$this->assertGreaterThan(time() + 14*60, strtotime($member->LockedOutUntil));
			}
			
			if($i > 5) {
				$this->assertTrue(false !== stripos($this->loginErrorMessage(), _t('Member.ERRORLOCKEDOUT')));
			}
		}
		
		/* THE USER CAN'T LOG IN NOW, EVEN IF THEY GET THE RIGHT PASSWORD */
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertNull($this->session()->inst_get('loggedInAs'));
		
		/* BUT, IF TIME PASSES, THEY CAN LOG IN */

		// (We fake this by re-setting LockedOutUntil)
		$member = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$member->LockedOutUntil = date('Y-m-d H:i:s', time() - 30);
		$member->write();
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertEquals($this->session()->inst_get('loggedInAs'), $member->ID);
		
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);

		/* NOW THAT THE LOCK-OUT HAS EXPIRED, CHECK THAT WE ARE ALLOWED 4 FAILED ATTEMPTS BEFORE LOGGING IN */

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$this->assertNull($this->session()->inst_get('loggedInAs'));
		$this->assertTrue(false !== stripos($this->loginErrorMessage(), _t('Member.ERRORWRONGCRED')));
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertEquals($this->session()->inst_get('loggedInAs'), $member->ID);
	}
	
	function testAlternatingRepeatedLoginAttempts() {
		Member::lock_out_after_incorrect_logins(3);
		
		// ATTEMPTING LOG-IN TWICE WITH ONE ACCOUNT AND TWICE WITH ANOTHER SHOULDN'T LOCK ANYBODY OUT

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');

		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword');
		
		$member1 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$member2 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'noexpiry'));
		
		$this->assertNull($member1->LockedOutUntil);
		$this->assertNull($member2->LockedOutUntil);
		
		// BUT, DOING AN ADDITIONAL LOG-IN WITH EITHER OF THEM WILL LOCK OUT, SINCE THAT IS THE 3RD FAILURE IN THIS SESSION

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$member1 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$this->assertNotNull($member1->LockedOutUntil);

		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword');
		$member2 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'noexpiry'));
		$this->assertNotNull($member2->LockedOutUntil);
	}
	
	function testUnsuccessfulLoginAttempts() {
		Security::set_login_recording(true);
		
		/* UNSUCCESSFUL ATTEMPTS WITH WRONG PASSWORD FOR EXISTING USER ARE LOGGED */
		$this->doTestLoginForm('sam@silverstripe.com', 'wrongpassword');
		$attempt = DataObject::get_one('LoginAttempt', 'Email = "sam@silverstripe.com"');
		$this->assertTrue(is_object($attempt));
		$member = DataObject::get_one('Member', 'Email = "sam@silverstripe.com"');
		$this->assertEquals($attempt->Status, 'Failure');
		$this->assertEquals($attempt->Email, 'sam@silverstripe.com');
		$this->assertEquals($attempt->Member(), $member);
		
		/* UNSUCCESSFUL ATTEMPTS WITH NONEXISTING USER ARE LOGGED */
		$this->doTestLoginForm('wronguser@silverstripe.com', 'wrongpassword');
		$attempt = DataObject::get_one('LoginAttempt', 'Email = "wronguser@silverstripe.com"');
		$this->assertTrue(is_object($attempt));
		$this->assertEquals($attempt->Status, 'Failure');
		$this->assertEquals($attempt->Email, 'wronguser@silverstripe.com');
	}
	
	function testSuccessfulLoginAttempts() {
		Security::set_login_recording(true);
		
		/* SUCCESSFUL ATTEMPTS ARE LOGGED */
		$this->doTestLoginForm('sam@silverstripe.com', '1nitialPassword');
		$attempt = DataObject::get_one('LoginAttempt', 'Email = "sam@silverstripe.com"');
		$member = DataObject::get_one('Member', 'Email = "sam@silverstripe.com"');
		$this->assertTrue(is_object($attempt));
		$this->assertEquals($attempt->Status, 'Success');
		$this->assertEquals($attempt->Email, 'sam@silverstripe.com');
		$this->assertEquals($attempt->Member(), $member);
	}

	/**
	 * Execute a log-in form using Director::test().
	 * Helper method for the tests above
	 */
	function doTestLoginForm($email, $password, $backURL = 'test/link') {
		$this->session()->inst_set('BackURL', $backURL);

		$this->get('Security/logout');
		$this->get('Security/login');
		
		return $this->submitForm(
			"MemberLoginForm_LoginForm", 
			null,
			array(
				'Email' => $email, 
				'Password' => $password, 
				'AuthenticationMethod' => 'MemberAuthenticator',
				'action_dologin' => 1,
			)
		); 
	}
	
	/**
	 * Get the error message on the login form
	 */
	function loginErrorMessage() {
		return $this->session()->inst_get('FormInfo.MemberLoginForm_LoginForm.formError.message');
	}	
	
}
?>