<?php
/**
 * Test the security class, including log-in form, change password form, etc
 *
 * @package framework
 * @subpackage tests
 */
class SecurityTest extends FunctionalTest {
	protected static $fixture_file = 'MemberTest.yml';
	
	protected $autoFollowRedirection = false;
	
	protected $priorAuthenticators = array();
	
	protected $priorDefaultAuthenticator = null;

	protected $priorUniqueIdentifierField = null;
	
	protected $priorRememberUsername = null;

	public function setUp() {
		// This test assumes that MemberAuthenticator is present and the default
		$this->priorAuthenticators = Authenticator::get_authenticators();
		$this->priorDefaultAuthenticator = Authenticator::get_default_authenticator();
		foreach($this->priorAuthenticators as $authenticator) {
			Authenticator::unregister($authenticator);
		}

		Authenticator::register('MemberAuthenticator');
		Authenticator::set_default_authenticator('MemberAuthenticator');

		// And that the unique identified field is 'Email'
		$this->priorUniqueIdentifierField = Member::config()->unique_identifier_field;
		$this->priorRememberUsername = Security::config()->remember_username;
		Member::config()->unique_identifier_field = 'Email';

		parent::setUp();
	}
	
	public function tearDown() {
		// Restore selected authenticator
		
		// MemberAuthenticator might not actually be present
		if(!in_array('MemberAuthenticator', $this->priorAuthenticators)) {
			Authenticator::unregister('MemberAuthenticator');
		}
		foreach($this->priorAuthenticators as $authenticator) {
			Authenticator::register($authenticator);
		}
		Authenticator::set_default_authenticator($this->priorDefaultAuthenticator);

		// Restore unique identifier field
		Member::config()->unique_identifier_field = $this->priorUniqueIdentifierField;
		Security::config()->remember_username = $this->priorRememberUsername;
		
		parent::tearDown();
	}
	
	public function testAccessingAuthenticatedPageRedirectsToLoginForm() {
		$this->autoFollowRedirection = false;
		
		$response = $this->get('SecurityTest_SecuredController');
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertContains(
			Config::inst()->get('Security', 'login_url'), 
			$response->getHeader('Location')
		);

		$this->logInWithPermission('ADMIN');		
		$response = $this->get('SecurityTest_SecuredController');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertContains('Success', $response->getBody());
		
		$this->autoFollowRedirection = true;
	}

	public function testPermissionFailureSetsCorrectFormMessages() {
		Config::nest();

		// Controller that doesn't attempt redirections
		$controller = new SecurityTest_NullController();
		$controller->response = new SS_HTTPResponse();

		Security::permissionFailure($controller, array('default' => 'Oops, not allowed'));
		$this->assertEquals('Oops, not allowed', Session::get('Security.Message.message'));

		// Test that config values are used correctly
		Config::inst()->update('Security', 'default_message_set', 'stringvalue');
		Security::permissionFailure($controller);
		$this->assertEquals('stringvalue', Session::get('Security.Message.message'),
			'Default permission failure message value was not present');

		Config::inst()->remove('Security', 'default_message_set');
		Config::inst()->update('Security', 'default_message_set', array('default' => 'arrayvalue'));
		Security::permissionFailure($controller);
		$this->assertEquals('arrayvalue', Session::get('Security.Message.message'),
			'Default permission failure message value was not present');

		// Test that non-default messages work.
		// NOTE: we inspect the response body here as the session message has already
		// been fetched and output as part of it, so has been removed from the session
		$this->logInWithPermission('EDITOR');

		Config::inst()->update('Security', 'default_message_set',
			array('default' => 'default', 'alreadyLoggedIn' => 'You are already logged in!'));
		Security::permissionFailure($controller);
		$this->assertContains('You are already logged in!', $controller->response->getBody(),
			'Custom permission failure message was ignored');

		Security::permissionFailure($controller,
			array('default' => 'default', 'alreadyLoggedIn' => 'One-off failure message'));
		$this->assertContains('One-off failure message', $controller->response->getBody(),
			"Message set passed to Security::permissionFailure() didn't override Config values");

		Config::unnest();
	}
	
	public function testLogInAsSomeoneElse() {
		$member = DataObject::get_one('Member');

		/* Log in with any user that we can find */
		$this->session()->inst_set('loggedInAs', $member->ID);

		/* View the Security/login page */
		$response = $this->get(Config::inst()->get('Security', 'login_url'));
		
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
	
	public function testMemberIDInSessionDoesntExistInDatabaseHasToLogin() {
		/* Log in with a Member ID that doesn't exist in the DB */
		$this->session()->inst_set('loggedInAs', 500);

		$this->autoFollowRedirection = true;
		
		/* Attempt to get into the admin section */
		$response = $this->get(Config::inst()->get('Security', 'login_url'));
		
		$items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm input.text');

		/* We have 2 text inputs - one for email, and another for the password */
		$this->assertEquals(count($items), 2, 'There are 2 inputs - one for email, another for password');

		$this->autoFollowRedirection = false;
		
		/* Log the user out */
		$this->session()->inst_set('loggedInAs', null);
	}
	
	public function testLoginUsernamePersists() {
		// Test that username does not persist
		$this->session()->inst_set('SessionForms.MemberLoginForm.Email', 'myuser@silverstripe.com');
		Security::config()->remember_username = false;
		$this->get(Config::inst()->get('Security', 'login_url'));
		$items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm #Email input.text');
		$this->assertEquals(1, count($items));
		$this->assertEmpty((string)$items[0]->attributes()->value);
		$this->assertEquals('off', (string)$items[0]->attributes()->autocomplete);
		$form = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm');
		$this->assertEquals(1, count($form));
		$this->assertEquals('off', (string)$form[0]->attributes()->autocomplete);
		
		// Test that username does persist when necessary
		$this->session()->inst_set('SessionForms.MemberLoginForm.Email', 'myuser@silverstripe.com');
		Security::config()->remember_username = true;
		$this->get(Config::inst()->get('Security', 'login_url'));
		$items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm #Email input.text');
		$this->assertEquals(1, count($items));
		$this->assertEquals('myuser@silverstripe.com', (string)$items[0]->attributes()->value);
		$this->assertNotEquals('off', (string)$items[0]->attributes()->autocomplete);
		$form = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm');
		$this->assertEquals(1, count($form));
		$this->assertNotEquals('off', (string)$form[0]->attributes()->autocomplete);
	}
	
	public function testExternalBackUrlRedirectionDisallowed() {
		// Test internal relative redirect
		$response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', 'testpage');
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertRegExp('/testpage/', $response->getHeader('Location'),
			"Internal relative BackURLs work when passed through to login form"
		);
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);
		
		// Test internal absolute redirect
		$response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword',
			Director::absoluteBaseURL() . 'testpage');
		// for some reason the redirect happens to a relative URL
		$this->assertRegExp('/^' . preg_quote(Director::absoluteBaseURL(), '/') . 'testpage/',
			$response->getHeader('Location'),
			"Internal absolute BackURLs work when passed through to login form"
		);
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);
		
		// Test external redirect
		$response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', 'http://myspoofedhost.com');
		$this->assertNotRegExp('/^' . preg_quote('http://myspoofedhost.com', '/') . '/',
			(string)$response->getHeader('Location'),
			"Redirection to external links in login form BackURL gets prevented as a measure against spoofing attacks"
		);

		// Test external redirection on ChangePasswordForm
		$this->get('Security/changepassword?BackURL=http://myspoofedhost.com');
		$changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
		$this->assertNotRegExp('/^' . preg_quote('http://myspoofedhost.com', '/') . '/',
			(string)$changedResponse->getHeader('Location'),
			"Redirection to external links in change password form BackURL gets prevented to stop spoofing attacks"
		);
				
		// Log the user out
		$this->session()->inst_set('loggedInAs', null);
	}
	
	/**
	 * Test that the login form redirects to the change password form after logging in with an expired password
	 */
	public function testExpiredPassword() {
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
		$this->assertEquals($this->idFromFixture('Member', 'expiredpassword'), 
			$this->session()->inst_get('loggedInAs'));

		// Make sure it redirects correctly after the password has been changed
		$this->mainSession->followRedirection();
		$changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
		$this->assertEquals(302, $changedResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'test/link', $changedResponse->getHeader('Location'));
	}
	
	public function testChangePasswordForLoggedInUsers() {
		$goodResponse = $this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		
		// Change the password
		$this->get('Security/changepassword?BackURL=test/back');
		$changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
		$this->assertEquals(302, $changedResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'test/back', $changedResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'test'), $this->session()->inst_get('loggedInAs'));
		
		// Check if we can login with the new password
		$goodResponse = $this->doTestLoginForm('sam@silverstripe.com' , 'changedPassword');
		$this->assertEquals(302, $goodResponse->getStatusCode());
		$this->assertEquals(Director::baseURL() . 'test/link', $goodResponse->getHeader('Location'));
		$this->assertEquals($this->idFromFixture('Member', 'test'), $this->session()->inst_get('loggedInAs'));
	}
	
	public function testChangePasswordFromLostPassword() {
		$admin = $this->objFromFixture('Member', 'test');
		$admin->FailedLoginCount = 99;
		$admin->LockedOutUntil = SS_Datetime::now()->Format('Y-m-d H:i:s');
		$admin->write();

		$this->assertNull($admin->AutoLoginHash, 'Hash is empty before lost password');
		
		// Request new password by email
		$response = $this->get('Security/lostpassword');
		$response = $this->post('Security/LostPasswordForm', array('Email' => 'sam@silverstripe.com'));
		
		$this->assertEmailSent('sam@silverstripe.com');
		
		// Load password link from email
		$admin = DataObject::get_by_id('Member', $admin->ID);
		$this->assertNotNull($admin->AutoLoginHash, 'Hash has been written after lost password');

		// We don't have access to the token - generate a new token and hash pair.
		$token = $admin->generateAutologinTokenAndStoreHash();

		// Check.
		$response = $this->get('Security/changepassword/?m='.$admin->ID.'&t=' . $token);
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals(Director::baseUrl() . 'Security/changepassword', $response->getHeader('Location'));
		
		// Follow redirection to form without hash in GET parameter
		$response = $this->get('Security/changepassword');
		$changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
		$this->assertEquals($this->idFromFixture('Member', 'test'), $this->session()->inst_get('loggedInAs'));
		
		// Check if we can login with the new password
		$goodResponse = $this->doTestLoginForm('sam@silverstripe.com' , 'changedPassword');
		$this->assertEquals(302, $goodResponse->getStatusCode());
		$this->assertEquals($this->idFromFixture('Member', 'test'), $this->session()->inst_get('loggedInAs'));

		$admin = DataObject::get_by_id('Member', $admin->ID, false);
		$this->assertNull($admin->LockedOutUntil);
		$this->assertEquals(0, $admin->FailedLoginCount);
	}
		
	public function testRepeatedLoginAttemptsLockingPeopleOut() {
		$local = i18n::get_locale();
		i18n::set_locale('en_US');

		Member::config()->lock_out_after_incorrect_logins = 5;
		Member::config()->lock_out_delay_mins = 15;
		
		// Login with a wrong password for more than the defined threshold
		for($i = 1; $i <= Member::config()->lock_out_after_incorrect_logins+1; $i++) {
			$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
			$member = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
			
			if($i < Member::config()->lock_out_after_incorrect_logins) {
				$this->assertNull(
					$member->LockedOutUntil,
					'User does not have a lockout time set if under threshold for failed attempts'
				);
				$this->assertContains($this->loginErrorMessage(), Convert::raw2xml(_t('Member.ERRORWRONGCRED')));
			} else {
				// Fuzzy matching for time to avoid side effects from slow running tests
				$this->assertGreaterThan(
					time() + 14*60, 
					strtotime($member->LockedOutUntil),
					'User has a lockout time set after too many failed attempts'
				);
			}

			$msg = _t(
				'Member.ERRORLOCKEDOUT2',
				'Your account has been temporarily disabled because of too many failed attempts at ' .
				'logging in. Please try again in {count} minutes.',
				null,
				array('count' => Member::config()->lock_out_delay_mins)
			);			
			if($i > Member::config()->lock_out_after_incorrect_logins) {
				$this->assertContains($msg, $this->loginErrorMessage());
			}
		}
		
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertNull(
			$this->session()->inst_get('loggedInAs'),
			'The user can\'t log in after being locked out, even with the right password'
		);
		
		// (We fake this by re-setting LockedOutUntil)
		$member = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$member->LockedOutUntil = date('Y-m-d H:i:s', time() - 30);
		$member->write();
		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertEquals(
			$this->session()->inst_get('loggedInAs'), 
			$member->ID,
			'After lockout expires, the user can login again'
		);

		// Log the user out
		$this->session()->inst_set('loggedInAs', null);

		// Login again with wrong password, but less attempts than threshold
		for($i = 1; $i < Member::config()->lock_out_after_incorrect_logins; $i++) {
			$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		}
		$this->assertNull($this->session()->inst_get('loggedInAs'));
		$this->assertContains(
			$this->loginErrorMessage(),
			Convert::raw2xml(_t('Member.ERRORWRONGCRED')),
			'The user can retry with a wrong password after the lockout expires'
		);

		$this->doTestLoginForm('sam@silverstripe.com' , '1nitialPassword');
		$this->assertEquals(
			$this->session()->inst_get('loggedInAs'), 
			$member->ID,
			'The user can login successfully after lockout expires, if staying below the threshold'
		);

		i18n::set_locale($local);
	}
	
	public function testAlternatingRepeatedLoginAttempts() {
		Member::config()->lock_out_after_incorrect_logins = 3;
		
		// ATTEMPTING LOG-IN TWICE WITH ONE ACCOUNT AND TWICE WITH ANOTHER SHOULDN'T LOCK ANYBODY OUT

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');

		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword');
		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword');
		
		$member1 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$member2 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'noexpiry'));
		
		$this->assertNull($member1->LockedOutUntil);
		$this->assertNull($member2->LockedOutUntil);
		
		// BUT, DOING AN ADDITIONAL LOG-IN WITH EITHER OF THEM WILL LOCK OUT, SINCE THAT IS THE 3RD FAILURE IN
		// THIS SESSION

		$this->doTestLoginForm('sam@silverstripe.com' , 'incorrectpassword');
		$member1 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'test'));
		$this->assertNotNull($member1->LockedOutUntil);

		$this->doTestLoginForm('noexpiry@silverstripe.com' , 'incorrectpassword');
		$member2 = DataObject::get_by_id("Member", $this->idFromFixture('Member', 'noexpiry'));
		$this->assertNotNull($member2->LockedOutUntil);
	}
	
	public function testUnsuccessfulLoginAttempts() {
		Security::config()->login_recording = true;
		
		/* UNSUCCESSFUL ATTEMPTS WITH WRONG PASSWORD FOR EXISTING USER ARE LOGGED */
		$this->doTestLoginForm('sam@silverstripe.com', 'wrongpassword');
		$attempt = DataObject::get_one('LoginAttempt', "\"Email\" = 'sam@silverstripe.com'");
		$this->assertTrue(is_object($attempt));
		$member = DataObject::get_one('Member', "\"Email\" = 'sam@silverstripe.com'");
		$this->assertEquals($attempt->Status, 'Failure');
		$this->assertEquals($attempt->Email, 'sam@silverstripe.com');
		$this->assertEquals($attempt->Member(), $member);
		
		/* UNSUCCESSFUL ATTEMPTS WITH NONEXISTING USER ARE LOGGED */
		$this->doTestLoginForm('wronguser@silverstripe.com', 'wrongpassword');
		$attempt = DataObject::get_one('LoginAttempt', "\"Email\" = 'wronguser@silverstripe.com'");
		$this->assertTrue(is_object($attempt));
		$this->assertEquals($attempt->Status, 'Failure');
		$this->assertEquals($attempt->Email, 'wronguser@silverstripe.com');
		$this->assertNotNull(
			$this->loginErrorMessage(), 'An invalid email returns a message.'
		);
	}
	
	public function testSuccessfulLoginAttempts() {
		Security::config()->login_recording = true;
		
		/* SUCCESSFUL ATTEMPTS ARE LOGGED */
		$this->doTestLoginForm('sam@silverstripe.com', '1nitialPassword');
		$attempt = DataObject::get_one('LoginAttempt', "\"Email\" = 'sam@silverstripe.com'");
		$member = DataObject::get_one('Member', "\"Email\" = 'sam@silverstripe.com'");
		$this->assertTrue(is_object($attempt));
		$this->assertEquals($attempt->Status, 'Success');
		$this->assertEquals($attempt->Email, 'sam@silverstripe.com');
		$this->assertEquals($attempt->Member(), $member);
	}
	
	public function testDatabaseIsReadyWithInsufficientMemberColumns() {
		$old = Security::$force_database_is_ready;
		Security::$force_database_is_ready = null;
		
		// Assumption: The database has been built correctly by the test runner,
		// and has all columns present in the ORM
		DB::getConn()->renameField('Member', 'Email', 'Email_renamed');
		
		// Email column is now missing, which means we're not ready to do permission checks
		$this->assertFalse(Security::database_is_ready());
		
		// Rebuild the database (which re-adds the Email column), and try again
		$this->resetDBSchema(true);
		$this->assertTrue(Security::database_is_ready());
		
		Security::$force_database_is_ready = $old;
	}

	/**
	 * Execute a log-in form using Director::test().
	 * Helper method for the tests above
	 */
	public function doTestLoginForm($email, $password, $backURL = 'test/link') {
		$this->get('Security/logout');
		$this->session()->inst_set('BackURL', $backURL);
		$this->get(Config::inst()->get('Security', 'login_url'));
		
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
	 * Helper method to execute a change password form
	 */
	public function doTestChangepasswordForm($oldPassword, $newPassword) {
		return $this->submitForm(
			"ChangePasswordForm_ChangePasswordForm", 
			null,
			array(
				'OldPassword' => $oldPassword, 
				'NewPassword1' => $newPassword, 
				'NewPassword2' => $newPassword,
				'action_doChangePassword' => 1,
			)
		);
	}
	
	/**
	 * Get the error message on the login form
	 */
	public function loginErrorMessage() {
		return $this->session()->inst_get('FormInfo.MemberLoginForm_LoginForm.formError.message');
	}	
	
}

class SecurityTest_SecuredController extends Controller implements TestOnly {

	private static $allowed_actions = array('index');

	public function index() {
		if(!Permission::check('ADMIN')) return Security::permissionFailure($this);
		
		return 'Success';
	}
}

class SecurityTest_NullController extends Controller implements TestOnly {

	public function redirect($url, $code = 302) {
		// NOOP
	}

}
