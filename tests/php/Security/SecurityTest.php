<?php

namespace SilverStripe\Security\Tests;

use Page;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBClassName;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * Test the security class, including log-in form, change password form, etc
 *
 * @skipUpgrade
 */
class SecurityTest extends FunctionalTest
{
    protected static $fixture_file = 'MemberTest.yml';

    protected $autoFollowRedirection = false;

    protected static $extra_controllers = [
        SecurityTest\NullController::class,
        SecurityTest\SecuredController::class,
    ];

    protected function setUp()
    {
        // Set to an empty array of authenticators to enable the default
        Config::modify()->set(MemberAuthenticator::class, 'authenticators', []);
        Config::modify()->set(MemberAuthenticator::class, 'default_authenticator', MemberAuthenticator::class);

        /**
         * @skipUpgrade
         */
        Member::config()->set('unique_identifier_field', 'Email');

        parent::setUp();

        Director::config()->set('alternate_base_url', '/');
    }

    public function testAccessingAuthenticatedPageRedirectsToLoginForm()
    {
        $this->autoFollowRedirection = false;

        $response = $this->get('SecurityTest_SecuredController');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertContains(
            Config::inst()->get(Security::class, 'login_url'),
            $response->getHeader('Location')
        );

        $this->logInWithPermission('ADMIN');
        $response = $this->get('SecurityTest_SecuredController');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Success', $response->getBody());

        $this->autoFollowRedirection = true;
    }

    public function testPermissionFailureSetsCorrectFormMessages()
    {
        // Controller that doesn't attempt redirections
        $controller = new SecurityTest\NullController();
        $controller->setRequest(Controller::curr()->getRequest());
        $controller->setResponse(new HTTPResponse());

        $session = Controller::curr()->getRequest()->getSession();
        Security::permissionFailure($controller, array('default' => 'Oops, not allowed'));
        $this->assertEquals('Oops, not allowed', $session->get('Security.Message.message'));

        // Test that config values are used correctly
        Config::modify()->set(Security::class, 'default_message_set', 'stringvalue');
        Security::permissionFailure($controller);
        $this->assertEquals(
            'stringvalue',
            $session->get('Security.Message.message'),
            'Default permission failure message value was not present'
        );

        Config::modify()->remove(Security::class, 'default_message_set');
        Config::modify()->merge(Security::class, 'default_message_set', array('default' => 'arrayvalue'));
        Security::permissionFailure($controller);
        $this->assertEquals(
            'arrayvalue',
            $session->get('Security.Message.message'),
            'Default permission failure message value was not present'
        );

        // Test that non-default messages work.
        // NOTE: we inspect the response body here as the session message has already
        // been fetched and output as part of it, so has been removed from the session
        $this->logInWithPermission('EDITOR');

        Config::modify()->set(
            Security::class,
            'default_message_set',
            array('default' => 'default', 'alreadyLoggedIn' => 'You are already logged in!')
        );
        Security::permissionFailure($controller);
        $this->assertContains(
            'You are already logged in!',
            $controller->getResponse()->getBody(),
            'Custom permission failure message was ignored'
        );

        Security::permissionFailure(
            $controller,
            array('default' => 'default', 'alreadyLoggedIn' => 'One-off failure message')
        );
        $this->assertContains(
            'One-off failure message',
            $controller->getResponse()->getBody(),
            "Message set passed to Security::permissionFailure() didn't override Config values"
        );
    }

    /**
     * Follow all redirects recursively
     *
     * @param  string $url
     * @param  int    $limit Max number of requests
     * @return HTTPResponse
     */
    protected function getRecursive($url, $limit = 10)
    {
        $this->cssParser = null;
        $response = $this->mainSession->get($url);
        while (--$limit > 0 && $response instanceof HTTPResponse && $response->getHeader('Location')) {
            $response = $this->mainSession->followRedirection();
        }
        return $response;
    }

    public function testAutomaticRedirectionOnLogin()
    {
        // BackURL with permission error (not authenticated) should not redirect
        if ($member = Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }
        $response = $this->getRecursive('SecurityTest_SecuredController');
        $this->assertContains(Convert::raw2xml("That page is secured."), $response->getBody());
        $this->assertContains('<input type="submit" name="action_doLogin"', $response->getBody());

        // Non-logged in user should not be redirected, but instead shown the login form
        // No message/context is available as the user has not attempted to view the secured controller
        $response = $this->getRecursive('Security/login?BackURL=SecurityTest_SecuredController/');
        $this->assertNotContains(Convert::raw2xml("That page is secured."), $response->getBody());
        $this->assertNotContains(Convert::raw2xml("You don't have access to this page"), $response->getBody());
        $this->assertContains('<input type="submit" name="action_doLogin"', $response->getBody());

        // BackURL with permission error (wrong permissions) should not redirect
        $this->logInAs('grouplessmember');
        $response = $this->getRecursive('SecurityTest_SecuredController');
        $this->assertContains(Convert::raw2xml("You don't have access to this page"), $response->getBody());
        $this->assertContains(
            '<input type="submit" name="action_logout" value="Log in as someone else"',
            $response->getBody()
        );

        // Directly accessing this page should attempt to follow the BackURL, but stop when it encounters the error
        $response = $this->getRecursive('Security/login?BackURL=SecurityTest_SecuredController/');
        $this->assertContains(Convert::raw2xml("You don't have access to this page"), $response->getBody());
        $this->assertContains(
            '<input type="submit" name="action_logout" value="Log in as someone else"',
            $response->getBody()
        );

        // Check correctly logged in admin doesn't generate the same errors
        $this->logInAs('admin');
        $response = $this->getRecursive('SecurityTest_SecuredController');
        $this->assertContains(Convert::raw2xml("Success"), $response->getBody());

        // Directly accessing this page should attempt to follow the BackURL and succeed
        $response = $this->getRecursive('Security/login?BackURL=SecurityTest_SecuredController/');
        $this->assertContains(Convert::raw2xml("Success"), $response->getBody());
    }

    public function testLogInAsSomeoneElse()
    {
        $member = DataObject::get_one(Member::class);

        /* Log in with any user that we can find */
        Security::setCurrentUser($member);

        /* View the Security/login page */
        $this->get(Config::inst()->get(Security::class, 'login_url'));

        $items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm input.action');

        /* We have only 1 input, one to allow the user to log in as someone else */
        $this->assertEquals(count($items), 1, 'There is 1 input, allowing the user to log in as someone else.');

        /* Submit the form, using only the logout action and a hidden field for the authenticator */
        $response = $this->submitForm(
            'MemberLoginForm_LoginForm',
            null,
            array(
                'action_logout' => 1,
            )
        );

        /* We get a good response */
        $this->assertEquals($response->getStatusCode(), 302, 'We have a redirection response');

        /* Log the user out */
        Security::setCurrentUser(null);
    }

    public function testMemberIDInSessionDoesntExistInDatabaseHasToLogin()
    {
        /* Log in with a Member ID that doesn't exist in the DB */
        $this->session()->set('loggedInAs', 500);

        $this->autoFollowRedirection = true;

        /* Attempt to get into the admin section */
        $this->get(Config::inst()->get(Security::class, 'login_url'));

        $items = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm input.text');

        /* We have 2 text inputs - one for email, and another for the password */
        $this->assertEquals(count($items), 2, 'There are 2 inputs - one for email, another for password');

        $this->autoFollowRedirection = false;

        /* Log the user out */
        $this->session()->set('loggedInAs', null);
    }

    public function testLoginUsernamePersists()
    {
        // Test that username does not persist
        $this->session()->set('SessionForms.MemberLoginForm.Email', 'myuser@silverstripe.com');
        Security::config()->set('remember_username', false);
        $this->get(Config::inst()->get(Security::class, 'login_url'));
        $items = $this
            ->cssParser()
            ->getBySelector('#MemberLoginForm_LoginForm #MemberLoginForm_LoginForm_Email');
        $this->assertEquals(1, count($items));
        $this->assertEmpty((string)$items[0]->attributes()->value);
        $this->assertEquals('off', (string)$items[0]->attributes()->autocomplete);
        $form = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm');
        $this->assertEquals(1, count($form));
        $this->assertEquals('off', (string)$form[0]->attributes()->autocomplete);

        // Test that username does persist when necessary
        $this->session()->set('SessionForms.MemberLoginForm.Email', 'myuser@silverstripe.com');
        Security::config()->set('remember_username', true);
        $this->get(Config::inst()->get(Security::class, 'login_url'));
        $items = $this
            ->cssParser()
            ->getBySelector('#MemberLoginForm_LoginForm #MemberLoginForm_LoginForm_Email');
        $this->assertEquals(1, count($items));
        $this->assertEquals('myuser@silverstripe.com', (string)$items[0]->attributes()->value);
        $this->assertNotEquals('off', (string)$items[0]->attributes()->autocomplete);
        $form = $this->cssParser()->getBySelector('#MemberLoginForm_LoginForm');
        $this->assertEquals(1, count($form));
        $this->assertNotEquals('off', (string)$form[0]->attributes()->autocomplete);
    }

    public function testLogout()
    {
        /* Enable SecurityToken */
        $securityTokenWasEnabled = SecurityToken::is_enabled();
        SecurityToken::enable();

        $member = DataObject::get_one(Member::class);

        /* Log in with any user that we can find */
        Security::setCurrentUser($member);

        /* Visit the Security/logout page with a test referer, but without a security token */
        $this->get(
            Config::inst()->get(Security::class, 'logout_url'),
            null,
            ['Referer' => Director::absoluteBaseURL() . 'testpage']
        );

        /* Make sure the user is still logged in */
        $this->assertNotNull(Security::getCurrentUser(), 'User is still logged in.');

        $token = $this->cssParser()->getBySelector('#LogoutForm_Form #LogoutForm_Form_SecurityID');
        $actions = $this->cssParser()->getBySelector('#LogoutForm_Form input.action');

        /* We have a security token, and an action to allow the user to log out */
        $this->assertCount(1, $token, 'There is a hidden field containing a security token.');
        $this->assertCount(1, $actions, 'There is 1 action, allowing the user to log out.');

        /* Submit the form, using the logout action */
        $response = $this->submitForm(
            'LogoutForm_Form',
            null,
            array(
                'action_doLogout' => 1,
            )
        );

        /* We get a good response */
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRegExp(
            '/testpage/',
            $response->getHeader('Location'),
            "Logout form redirects to back to referer."
        );

        /* User is logged out successfully */
        $this->assertNull(Security::getCurrentUser(), 'User is logged out.');

        /* Re-disable SecurityToken */
        if (!$securityTokenWasEnabled) {
            SecurityToken::disable();
        }
    }

    public function testExternalBackUrlRedirectionDisallowed()
    {
        // Test internal relative redirect
        $response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', 'testpage');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRegExp(
            '/testpage/',
            $response->getHeader('Location'),
            "Internal relative BackURLs work when passed through to login form"
        );
        // Log the user out
        $this->session()->set('loggedInAs', null);

        // Test internal absolute redirect
        $response = $this->doTestLoginForm(
            'noexpiry@silverstripe.com',
            '1nitialPassword',
            Director::absoluteBaseURL() . 'testpage'
        );
        // for some reason the redirect happens to a relative URL
        $this->assertRegExp(
            '/^' . preg_quote(Director::absoluteBaseURL(), '/') . 'testpage/',
            $response->getHeader('Location'),
            "Internal absolute BackURLs work when passed through to login form"
        );
        // Log the user out
        $this->session()->set('loggedInAs', null);

        // Test external redirect
        $response = $this->doTestLoginForm('noexpiry@silverstripe.com', '1nitialPassword', 'http://myspoofedhost.com');
        $this->assertNotRegExp(
            '/^' . preg_quote('http://myspoofedhost.com', '/') . '/',
            (string)$response->getHeader('Location'),
            "Redirection to external links in login form BackURL gets prevented as a measure against spoofing attacks"
        );

        // Test external redirection on ChangePasswordForm
        $this->get('Security/changepassword?BackURL=http://myspoofedhost.com');
        $changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
        $this->assertNotRegExp(
            '/^' . preg_quote('http://myspoofedhost.com', '/') . '/',
            (string)$changedResponse->getHeader('Location'),
            "Redirection to external links in change password form BackURL gets prevented to stop spoofing attacks"
        );

        // Log the user out
        $this->session()->set('loggedInAs', null);
    }

    /**
     * Test that the login form redirects to the change password form after logging in with an expired password
     */
    public function testExpiredPassword()
    {
        /* BAD PASSWORDS ARE LOCKED OUT */
        $badResponse = $this->doTestLoginForm('testuser@example.com', 'badpassword');
        $this->assertEquals(302, $badResponse->getStatusCode());
        $this->assertRegExp('/Security\/login/', $badResponse->getHeader('Location'));
        $this->assertNull($this->session()->get('loggedInAs'));

        /* UNEXPIRED PASSWORD GO THROUGH WITHOUT A HITCH */
        $goodResponse = $this->doTestLoginForm('testuser@example.com', '1nitialPassword');
        $this->assertEquals(302, $goodResponse->getStatusCode());
        $this->assertEquals(
            Controller::join_links(Director::absoluteBaseURL(), 'test/link'),
            $goodResponse->getHeader('Location')
        );
        $this->assertEquals($this->idFromFixture(Member::class, 'test'), $this->session()->get('loggedInAs'));

        $this->logOut();

        /* EXPIRED PASSWORDS ARE SENT TO THE CHANGE PASSWORD FORM */
        $expiredResponse = $this->doTestLoginForm('expired@silverstripe.com', '1nitialPassword');
        $this->assertEquals(302, $expiredResponse->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL('Security/changepassword') . '?BackURL=test%2Flink',
            Director::absoluteURL($expiredResponse->getHeader('Location'))
        );
        $this->assertEquals(
            $this->idFromFixture(Member::class, 'expiredpassword'),
            $this->session()->get('loggedInAs')
        );

        // Make sure it redirects correctly after the password has been changed
        $this->mainSession->followRedirection();
        $changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
        $this->assertEquals(302, $changedResponse->getStatusCode());
        $this->assertEquals(
            Controller::join_links(Director::absoluteBaseURL(), 'test/link'),
            $changedResponse->getHeader('Location')
        );
    }

    public function testChangePasswordForLoggedInUsers()
    {
        $this->doTestLoginForm('testuser@example.com', '1nitialPassword');

        // Change the password
        $this->get('Security/changepassword?BackURL=test/back');
        $changedResponse = $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
        $this->assertEquals(302, $changedResponse->getStatusCode());
        $this->assertEquals(
            Controller::join_links(Director::absoluteBaseURL(), 'test/back'),
            $changedResponse->getHeader('Location')
        );
        $this->assertEquals($this->idFromFixture(Member::class, 'test'), $this->session()->get('loggedInAs'));

        // Check if we can login with the new password
        $this->logOut();
        $goodResponse = $this->doTestLoginForm('testuser@example.com', 'changedPassword');
        $this->assertEquals(302, $goodResponse->getStatusCode());
        $this->assertEquals(
            Controller::join_links(Director::absoluteBaseURL(), 'test/link'),
            $goodResponse->getHeader('Location')
        );
        $this->assertEquals($this->idFromFixture(Member::class, 'test'), $this->session()->get('loggedInAs'));
    }

    public function testChangePasswordFromLostPassword()
    {
        /** @var Member $admin */
        $admin = $this->objFromFixture(Member::class, 'test');
        $admin->FailedLoginCount = 99;
        $admin->LockedOutUntil = DBDatetime::now()->getValue();
        $admin->write();

        $this->assertNull($admin->AutoLoginHash, 'Hash is empty before lost password');

        // Request new password by email
        $this->get('Security/lostpassword');
        $this->post('Security/lostpassword/LostPasswordForm', array('Email' => 'testuser@example.com'));

        $this->assertEmailSent('testuser@example.com');

        // Load password link from email
        $admin = DataObject::get_by_id(Member::class, $admin->ID);
        $this->assertNotNull($admin->AutoLoginHash, 'Hash has been written after lost password');

        // We don't have access to the token - generate a new token and hash pair.
        $token = $admin->generateAutologinTokenAndStoreHash();

        // Check.
        $response = $this->get('Security/changepassword/?m=' . $admin->ID . '&t=' . $token);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL('Security/changepassword'),
            Director::absoluteURL($response->getHeader('Location'))
        );

        // Follow redirection to form without hash in GET parameter
        $this->get('Security/changepassword');
        $this->doTestChangepasswordForm('1nitialPassword', 'changedPassword');
        $this->assertEquals($this->idFromFixture(Member::class, 'test'), $this->session()->get('loggedInAs'));

        // Check if we can login with the new password
        $this->logOut();
        $goodResponse = $this->doTestLoginForm('testuser@example.com', 'changedPassword');
        $this->assertEquals(302, $goodResponse->getStatusCode());
        $this->assertEquals($this->idFromFixture(Member::class, 'test'), $this->session()->get('loggedInAs'));

        $admin = DataObject::get_by_id(Member::class, $admin->ID, false);
        $this->assertNull($admin->LockedOutUntil);
        $this->assertEquals(0, $admin->FailedLoginCount);
    }

    public function testRepeatedLoginAttemptsLockingPeopleOut()
    {
        i18n::set_locale('en_US');
        Member::config()->set('lock_out_after_incorrect_logins', 5);
        Member::config()->set('lock_out_delay_mins', 15);
        DBDatetime::set_mock_now('2017-05-22 00:00:00');

        // Login with a wrong password for more than the defined threshold
        /** @var Member $member */
        $member = null;
        for ($i = 1; $i <= 6; $i++) {
            $this->doTestLoginForm('testuser@example.com', 'incorrectpassword');
            /** @var Member $member */
            $member = DataObject::get_by_id(Member::class, $this->idFromFixture(Member::class, 'test'));

            if ($i < 5) {
                $this->assertNull(
                    $member->LockedOutUntil,
                    'User does not have a lockout time set if under threshold for failed attempts'
                );
                $this->assertHasMessage(
                    _t(
                        'SilverStripe\\Security\\Member.ERRORWRONGCRED',
                        'The provided details don\'t seem to be correct. Please try again.'
                    )
                );
            } else {
                // Lockout should be exactly 15 minutes from now
                /** @var DBDatetime $lockedOutUntilObj */
                $lockedOutUntilObj = $member->dbObject('LockedOutUntil');
                $this->assertEquals(
                    DBDatetime::now()->getTimestamp() + (15 * 60),
                    $lockedOutUntilObj->getTimestamp(),
                    'User has a lockout time set after too many failed attempts'
                );
            }
        }
        $msg = _t(
            'SilverStripe\\Security\\Member.ERRORLOCKEDOUT2',
            'Your account has been temporarily disabled because of too many failed attempts at ' . 'logging in. Please try again in {count} minutes.',
            null,
            array('count' => 15)
        );
        $this->assertHasMessage($msg);
        $this->doTestLoginForm('testuser@example.com', '1nitialPassword');
        $this->assertNull(
            $this->session()->get('loggedInAs'),
            'The user can\'t log in after being locked out, even with the right password'
        );

        // Move into the future so we can login again
        DBDatetime::set_mock_now('2017-06-22 00:00:00');
        $this->doTestLoginForm('testuser@example.com', '1nitialPassword');
        $this->assertEquals(
            $member->ID,
            $this->session()->get('loggedInAs'),
            'After lockout expires, the user can login again'
        );

        // Log the user out
        $this->logOut();

        // Login again with wrong password, but less attempts than threshold
        for ($i = 1; $i < 5; $i++) {
            $this->doTestLoginForm('testuser@example.com', 'incorrectpassword');
        }
        $this->assertNull($this->session()->get('loggedInAs'));
        $this->assertHasMessage(
            _t('SilverStripe\\Security\\Member.ERRORWRONGCRED', 'The provided details don\'t seem to be correct. Please try again.'),
            'The user can retry with a wrong password after the lockout expires'
        );

        $this->doTestLoginForm('testuser@example.com', '1nitialPassword');
        $this->assertEquals(
            $this->session()->get('loggedInAs'),
            $member->ID,
            'The user can login successfully after lockout expires, if staying below the threshold'
        );
    }

    public function testAlternatingRepeatedLoginAttempts()
    {
        Member::config()->set('lock_out_after_incorrect_logins', 3);

        // ATTEMPTING LOG-IN TWICE WITH ONE ACCOUNT AND TWICE WITH ANOTHER SHOULDN'T LOCK ANYBODY OUT

        $this->doTestLoginForm('testuser@example.com', 'incorrectpassword');
        $this->doTestLoginForm('testuser@example.com', 'incorrectpassword');

        $this->doTestLoginForm('noexpiry@silverstripe.com', 'incorrectpassword');
        $this->doTestLoginForm('noexpiry@silverstripe.com', 'incorrectpassword');

        /** @var Member $member1 */
        $member1 = DataObject::get_by_id(Member::class, $this->idFromFixture(Member::class, 'test'));
        /** @var Member $member2 */
        $member2 = DataObject::get_by_id(Member::class, $this->idFromFixture(Member::class, 'noexpiry'));

        $this->assertNull($member1->LockedOutUntil);
        $this->assertNull($member2->LockedOutUntil);

        // BUT, DOING AN ADDITIONAL LOG-IN WITH EITHER OF THEM WILL LOCK OUT, SINCE THAT IS THE 3RD FAILURE IN
        // THIS SESSION

        $this->doTestLoginForm('testuser@example.com', 'incorrectpassword');
        $member1 = DataObject::get_by_id(Member::class, $this->idFromFixture(Member::class, 'test'));
        $this->assertNotNull($member1->LockedOutUntil);

        $this->doTestLoginForm('noexpiry@silverstripe.com', 'incorrectpassword');
        $member2 = DataObject::get_by_id(Member::class, $this->idFromFixture(Member::class, 'noexpiry'));
        $this->assertNotNull($member2->LockedOutUntil);
    }

    public function testUnsuccessfulLoginAttempts()
    {
        Security::config()->set('login_recording', true);

        /* UNSUCCESSFUL ATTEMPTS WITH WRONG PASSWORD FOR EXISTING USER ARE LOGGED */
        $this->doTestLoginForm('testuser@example.com', 'wrongpassword');
        /** @var LoginAttempt $attempt */
        $attempt = LoginAttempt::getByEmail('testuser@example.com')->first();
        $this->assertInstanceOf(LoginAttempt::class, $attempt);
        $member = Member::get()->filter('Email', 'testuser@example.com')->first();
        $this->assertEquals($attempt->Status, 'Failure');
        $this->assertEmpty($attempt->Email); // Doesn't store potentially sensitive data
        $this->assertEquals($attempt->EmailHashed, sha1('testuser@example.com'));
        $this->assertEquals($attempt->Member()->toMap(), $member->toMap());

        /* UNSUCCESSFUL ATTEMPTS WITH NONEXISTING USER ARE LOGGED */
        $this->doTestLoginForm('wronguser@silverstripe.com', 'wrongpassword');
        $attempt = LoginAttempt::getByEmail('wronguser@silverstripe.com')->first();
        $this->assertInstanceOf(LoginAttempt::class, $attempt);
        $this->assertEquals($attempt->Status, 'Failure');
        $this->assertEmpty($attempt->Email); // Doesn't store potentially sensitive data
        $this->assertEquals($attempt->EmailHashed, sha1('wronguser@silverstripe.com'));
        $this->assertNotEmpty($this->getValidationResult()->getMessages(), 'An invalid email returns a message.');
    }

    public function testSuccessfulLoginAttempts()
    {
        Security::config()->set('login_recording', true);

        /* SUCCESSFUL ATTEMPTS ARE LOGGED */
        $this->doTestLoginForm('testuser@example.com', '1nitialPassword');
        /** @var LoginAttempt $attempt */
        $attempt = LoginAttempt::getByEmail('testuser@example.com')->first();
        $member = Member::get()->filter('Email', 'testuser@example.com')->first();
        $this->assertInstanceOf(LoginAttempt::class, $attempt);
        $this->assertEquals($attempt->Status, 'Success');
        $this->assertEmpty($attempt->Email); // Doesn't store potentially sensitive data
        $this->assertEquals($attempt->EmailHashed, sha1('testuser@example.com'));
        $this->assertEquals($attempt->Member()->toMap(), $member->toMap());
    }

    public function testDatabaseIsReadyWithInsufficientMemberColumns()
    {
        Security::clear_database_is_ready();
        DBClassName::clear_classname_cache();

        // Assumption: The database has been built correctly by the test runner,
        // and has all columns present in the ORM
        /**
         * @skipUpgrade
         */
        DB::get_schema()->renameField('Member', 'Email', 'Email_renamed');

        // Email column is now missing, which means we're not ready to do permission checks
        $this->assertFalse(Security::database_is_ready());

        // Rebuild the database (which re-adds the Email column), and try again
        static::resetDBSchema(true);
        $this->assertTrue(Security::database_is_ready());
    }

    public function testSecurityControllerSendsRobotsTagHeader()
    {
        $response = $this->get(Config::inst()->get(Security::class, 'login_url'));
        $robotsHeader = $response->getHeader('X-Robots-Tag');
        $this->assertNotNull($robotsHeader);
        $this->assertContains('noindex', $robotsHeader);
    }

    public function testDoNotSendEmptyRobotsHeaderIfNotDefined()
    {
        Config::modify()->remove(Security::class, 'robots_tag');
        $response = $this->get(Config::inst()->get(Security::class, 'login_url'));
        $robotsHeader = $response->getHeader('X-Robots-Tag');
        $this->assertNull($robotsHeader);
    }

    public function testGetResponseController()
    {
        if (!class_exists(Page::class)) {
            $this->markTestSkipped("This test requires CMS module");
        }

        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $security = new Security();
        $security->setRequest($request);
        $reflection = new \ReflectionClass($security);
        $method = $reflection->getMethod('getResponseController');
        $method->setAccessible(true);
        $result = $method->invoke($security, 'Page');

        // Ensure page shares the same controller as security
        $securityClass = Config::inst()->get(Security::class, 'page_class');
        /** @var Page $securityPage */
        $securityPage = new $securityClass();
        $this->assertInstanceOf($securityPage->getControllerName(), $result);
        $this->assertEquals($request, $result->getRequest());
    }

    /**
     * Execute a log-in form using Director::test().
     * Helper method for the tests above
     *
     * @param string $email
     * @param string $password
     * @param string $backURL
     * @return HTTPResponse
     */
    public function doTestLoginForm($email, $password, $backURL = 'test/link')
    {
        $this->get(Config::inst()->get(Security::class, 'logout_url'));
        $this->session()->set('BackURL', $backURL);
        $this->get(Config::inst()->get(Security::class, 'login_url'));

        return $this->submitForm(
            "MemberLoginForm_LoginForm",
            null,
            array(
                'Email' => $email,
                'Password' => $password,
                'AuthenticationMethod' => MemberAuthenticator::class,
                'action_doLogin' => 1,
            )
        );
    }

    /**
     * Helper method to execute a change password form
     *
     * @param string $oldPassword
     * @param string $newPassword
     * @return HTTPResponse
     */
    public function doTestChangepasswordForm($oldPassword, $newPassword)
    {
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
     * Assert this message is in the current login form errors
     *
     * @param string $expected
     * @param string $errorMessage
     */
    protected function assertHasMessage($expected, $errorMessage = null)
    {
        $messages = [];
        $result = $this->getValidationResult();
        if ($result) {
            foreach ($result->getMessages() as $message) {
                $messages[] = $message['message'];
            }
        }

        $this->assertContains($expected, $messages, $errorMessage);
    }

    /**
     * Get validation result from last login form submission
     *
     * @return ValidationResult
     */
    protected function getValidationResult()
    {
        $result = $this->session()->get('FormInfo.MemberLoginForm_LoginForm.result');
        if ($result) {
            return unserialize($result);
        }
        return null;
    }
}
