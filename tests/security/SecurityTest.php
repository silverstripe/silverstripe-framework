<?php
/**
 * Test the security class, including log-in form, change password form, etc
 *
 * @package sapphire
 * @subpackage tests
 */
class SecurityTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/security/MemberTest.yml';
	
	
	/**
	 * Test that the login form redirects to the change password form after logging in with an expired password
	 */
	function testExpiredPassword() {
		/* BAD PASSWORDS ARE LOCKED OUT */
		
		$session = new Session(array());
		$badResponse = $this->doTestLoginForm('sam@silverstripe.com' , 'badpassword', $session);
		$this->assertEquals(302, $badResponse->getStatusCode());
		$this->assertRegExp('/Security\/login/', $badResponse->getHeader('Location'));
		$this->assertNull($session->inst_get('loggedInAs'));

		/* UNEXPIRED PASSWORD GO THROUGH WITHOUT A HITCH */

		$session = new Session(array());
		$goodResponse = $this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword', $session);
		$this->assertEquals(302, $goodResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'test/link', $goodResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'test'), $session->inst_get('loggedInAs'));
		
		/* EXPIRED PASSWORDS ARE SENT TO THE CHANGE PASSWORD FORM */
		
		$session = new Session(array());
		$expiredResponse = $this->doTestLoginForm('expired@silverstripe.com' , '1nitialPassword', $session);
		$this->assertEquals(302, $expiredResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'Security/changepassword', $expiredResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'expiredpassword'), $session->inst_get('loggedInAs'));
	}
	
	function testRepeatedLoginAttemptsLockingPeopleOut() {
		$session = new Session(array());
		
		Member::lock_out_after_incorrect_logins(5);
		
		/* LOG IN WITH A BAD PASSWORD 7 TIMES */

		for($i=1;$i<=7;$i++) {
			$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
			$member = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
			
			/* THE FIRST 4 TIMES, THE MEMBER SHOULDN'T BE LOCKED OUT */
			if($i < 5) {
				$this->assertNull($member->LockedOutUntil);
				$this->assertTrue(false !== stripos($this->loginErrorMessage($session), "That doesn't seem to be the right e-mail address or password"));
			}
			
			/* AFTER THAT THE USER IS LOCKED OUT FOR 15 MINUTES */

			//(we check for at least 14 minutes because we don't want a slow running test to report a failure.)
			else {
				$this->assertGreaterThan(time() + 14*60, strtotime($member->LockedOutUntil));
			}
			
			if($i > 5) {
				$this->assertTrue(false !== stripos($this->loginErrorMessage($session), "Your account has been temporarily disabled"));
			}
		}
		
		/* THE USER CAN'T LOG IN NOW, EVEN IF THEY GET THE RIGHT PASSWORD */
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword', $session);
		$this->assertNull($session->inst_get('loggedInAs'));
		
		/* BUT, IF TIME PASSES, THEY CAN LOG IN */

		// (We fake this by re-setting LockedOutUntil)
		$member = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$member->LockedOutUntil = date('Y-m-d H:i:s', time() - 30);
		$member->write();
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword', $session);
		$this->assertEquals($session->inst_get('loggedInAs'), $member->ID);
		
		// Log the user out
		$session->inst_set('loggedInAs', null);

		/* NOW THAT THE LOCK-OUT HAS EXPIRED, CHECK THAT WE ARE ALLOWED 4 FAILED ATTEMPTS BEFORE LOGGING IN */

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
		$this->assertNull($session->inst_get('loggedInAs'));
		$this->assertTrue(false !== stripos($this->loginErrorMessage($session), "That doesn't seem to be the right e-mail address or password"));
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword', $session);
		$this->assertEquals($session->inst_get('loggedInAs'), $member->ID);
	}
	
	function testAlternatingRepeatedLoginAttempts() {
		$session = new Session(array());
		
		Member::lock_out_after_incorrect_logins(3);
		
		// ATTEMPTING LOG-IN TWICE WITH ONE ACCOUNT AND TWICE WITH ANOTHER SHOULDN'T LOCK ANYBODY OUT

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);

		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword', $session);
		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword', $session);
		
		$member1 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$member2 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'noexpiry'));
		
		$this->assertNull($member1->LockedOutUntil);
		$this->assertNull($member2->LockedOutUntil);
		
		// BUT, DOING AN ADDITIONAL LOG-IN WITH EITHER OF THEM WILL LOCK OUT, SINCE THAT IS THE 3RD FAILURE IN THIS SESSION

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword', $session);
		$member1 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$this->assertNotNull($member1->LockedOutUntil);

		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword', $session);
		$member2 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'noexpiry'));
		$this->assertNotNull($member2->LockedOutUntil);
	}
	
	function testUnsuccessfulLoginAttempts() {
		Security::set_login_recording(true);
		
		/* UNSUCCESSFUL ATTEMPTS WITH WRONG PASSWORD FOR EXISTING USER ARE LOGGED */
		$this->get('Security/login');
		$this->doTestLoginFormFunctional('sam@silverstripe.com', 'wrongpassword');
		$attempt = DataObject::get_one('LoginAttempt', 'Email = "sam@silverstripe.com"');
		$this->assertTrue(is_object($attempt));
		$member = DataObject::get_one('Member', 'Email = "sam@silverstripe.com"');
		$this->assertEquals($attempt->Status, 'Failure');
		$this->assertEquals($attempt->Email, 'sam@silverstripe.com');
		$this->assertEquals($attempt->Member(), $member);
		
		/* UNSUCCESSFUL ATTEMPTS WITH NONEXISTING USER ARE LOGGED */
		$this->get('Security/login');
		$this->doTestLoginFormFunctional('wronguser@silverstripe.com', 'wrongpassword');
		$attempt = DataObject::get_one('LoginAttempt', 'Email = "wronguser@silverstripe.com"');
		$this->assertTrue(is_object($attempt));
		$this->assertEquals($attempt->Status, 'Failure');
		$this->assertEquals($attempt->Email, 'wronguser@silverstripe.com');
	}
	
	function testSuccessfulLoginAttempts() {
		Security::set_login_recording(true);
		
		/* SUCCESSFUL ATTEMPTS ARE LOGGED */
		$this->get('Security/login');
		$this->doTestLoginFormFunctional('sam@silverstripe.com', '1nitialPassword');
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
	function doTestLoginForm($email, $password, &$session) {
		 return Director::test('Security/login?executeForm=LoginForm', array(
			'Email' => $email, 
			'Password' => $password, 
			'AuthenticationMethod' => 'MemberAuthenticator',
			'action_dologin' => 1,
			'BackURL' => 'test/link'),
			$session
		);
	}
	
	/**
	 * Execute a log-in form using Director::test().
	 * Helper method for the tests above
	 */
	function doTestLoginFormFunctional($email, $password) {
		$this->submitForm(
			"MemberLoginForm_LoginForm", 
			null,
			array(
				'Email' => $email, 
				'Password' => $password, 
				'AuthenticationMethod' => 'MemberAuthenticator',
				'action_dologin' => 1,
				'BackURL' => 'test/link'
			)
		); 
	}
	
	/**
	 * Get the error message on the login form
	 */
	function loginErrorMessage($session) {
		return $session->inst_get('FormInfo.MemberLoginForm_LoginForm.formError.message');
	}	
	
}
?>