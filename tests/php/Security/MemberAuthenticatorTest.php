<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\CMSMemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\CMSMemberLoginForm;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Security\Security;

/**
 * @skipUpgrade
 */
class MemberAuthenticatorTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected $defaultUsername = null;
    protected $defaultPassword = null;

    protected function setUp()
    {
        parent::setUp();

        if (DefaultAdminService::hasDefaultAdmin()) {
            $this->defaultUsername = DefaultAdminService::getDefaultAdminUsername();
            $this->defaultPassword = DefaultAdminService::getDefaultAdminPassword();
            DefaultAdminService::clearDefaultAdmin();
        } else {
            $this->defaultUsername = null;
            $this->defaultPassword = null;
        }
        DefaultAdminService::setDefaultAdmin('admin', 'password');
    }

    protected function tearDown()
    {
        DefaultAdminService::clearDefaultAdmin();
        if ($this->defaultUsername) {
            DefaultAdminService::setDefaultAdmin($this->defaultUsername, $this->defaultPassword);
        }
        parent::tearDown();
    }

    public function testCustomIdentifierField()
    {
        Member::config()->set('unique_identifier_field', 'Username');

        $label = Member::singleton()
            ->fieldLabel(Member::config()->get('unique_identifier_field'));

        $this->assertEquals($label, 'Username');
    }

    public function testGenerateLoginForm()
    {
        $authenticator = new MemberAuthenticator();

        $controller = new Security();

        // Create basic login form
        $frontendResponse = $authenticator
            ->getLoginHandler($controller->link())
            ->handleRequest(Controller::curr()->getRequest());

        $this->assertTrue(is_array($frontendResponse));
        $this->assertTrue(isset($frontendResponse['Form']));
        $this->assertTrue($frontendResponse['Form'] instanceof MemberLoginForm);
    }

    public function testGenerateCMSLoginForm()
    {
        /** @var CMSMemberAuthenticator $authenticator */
        $authenticator = new CMSMemberAuthenticator();

        // Supports cms login form
        $this->assertGreaterThan(0, ($authenticator->supportedServices() & Authenticator::CMS_LOGIN));
        $cmsHandler = $authenticator->getLoginHandler('/');
        $cmsForm = $cmsHandler->loginForm();
        $this->assertTrue($cmsForm instanceof CMSMemberLoginForm);
    }


    /**
     * Test that a member can be authenticated via their temp id
     */
    public function testAuthenticateByTempID()
    {
        $authenticator = new CMSMemberAuthenticator();

        $member = new Member();
        $member->Email = 'test1@test.com';
        $member->PasswordEncryption = "sha1";
        $member->Password = "mypassword";
        $member->write();

        // If the user has never logged in, then the tempid should be empty
        $tempID = $member->TempIDHash;
        $this->assertEmpty($tempID);

        // If the user logs in then they have a temp id
        Injector::inst()->get(IdentityStore::class)->logIn($member, true);
        $tempID = $member->TempIDHash;
        $this->assertNotEmpty($tempID);

        // Test correct login
        /** @var ValidationResult $message */
        $result = $authenticator->authenticate(
            [
            'tempid' => $tempID,
            'Password' => 'mypassword'
            ],
            Controller::curr()->getRequest(),
            $message
        );

        $this->assertNotEmpty($result);
        $this->assertEquals($result->ID, $member->ID);
        $this->assertTrue($message->isValid());

        // Test incorrect login
        $result = $authenticator->authenticate(
            [
            'tempid' => $tempID,
            'Password' => 'notmypassword'
            ],
            Controller::curr()->getRequest(),
            $message
        );

        $this->assertEmpty($result);
        $messages = $message->getMessages();
        $this->assertEquals(
            _t('SilverStripe\\Security\\Member.ERRORWRONGCRED', 'The provided details don\'t seem to be correct. Please try again.'),
            $messages[0]['message']
        );
    }

    public function testExpiredTempID()
    {
        DefaultAdminService::clearDefaultAdmin();

        $authenticator = new CMSMemberAuthenticator();

        // Make member with expired TempID
        $member = new Member();
        $member->Email = 'test1@test.com';
        $member->PasswordEncryption = "sha1";
        $member->Password = "mypassword";
        $member->TempIDExpired = '2016-05-22 00:00:00';
        $member->write();
        Injector::inst()->get(IdentityStore::class)->logIn($member, true);

        $tempID = $member->TempIDHash;

        DBDatetime::set_mock_now('2016-05-29 00:00:00');

        $this->assertNotEmpty($tempID);
        $this->assertFalse(DefaultAdminService::hasDefaultAdmin());

        $result = $authenticator->authenticate(array(
            'tempid' => $tempID,
            'Password' => 'notmypassword'
        ), Controller::curr()->getRequest(), $validationResult);

        $this->assertNull($result);
        $this->assertFalse($validationResult->isValid());
    }

    /**
     * Test that the default admin can be authenticated
     */
    public function testDefaultAdmin()
    {
        $authenticator = new MemberAuthenticator();

        // Test correct login
        /** @var ValidationResult $message */
        $result = $authenticator->authenticate(
            [
            'Email' => 'admin',
            'Password' => 'password'
            ],
            Controller::curr()->getRequest(),
            $message
        );
        $this->assertNotEmpty($result);
        $this->assertEquals($result->Email, DefaultAdminService::getDefaultAdminUsername());
        $this->assertTrue($message->isValid());

        // Test incorrect login
        $result = $authenticator->authenticate(
            [
            'Email' => 'admin',
            'Password' => 'notmypassword'
            ],
            Controller::curr()->getRequest(),
            $message
        );
        $messages = $message->getMessages();
        $this->assertEmpty($result);
        $this->assertEquals(
            'The provided details don\'t seem to be correct. Please try again.',
            $messages[0]['message']
        );
    }

    public function testDefaultAdminLockOut()
    {
        $authenticator = new MemberAuthenticator();

        Config::modify()->set(Member::class, 'lock_out_after_incorrect_logins', 1);
        Config::modify()->set(Member::class, 'lock_out_delay_mins', 10);
        DBDatetime::set_mock_now('2016-04-18 00:00:00');

        // Test correct login
        $authenticator->authenticate(
            [
                'Email' => 'admin',
                'Password' => 'wrongpassword'
            ],
            Controller::curr()->getRequest()
        );

        $defaultAdmin = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
        $this->assertNotNull($defaultAdmin);
        $this->assertFalse($defaultAdmin->canLogin());
        $this->assertEquals('2016-04-18 00:10:00', $defaultAdmin->LockedOutUntil);
    }

    public function testNonExistantMemberGetsLoginAttemptRecorded()
    {
        Security::config()->set('login_recording', true);
        Member::config()
            ->set('lock_out_after_incorrect_logins', 1)
            ->set('lock_out_delay_mins', 10);

        $email = 'notreal@example.com';
        $this->assertFalse(Member::get()->filter(array('Email' => $email))->exists());
        $this->assertCount(0, LoginAttempt::get());
        $authenticator = new MemberAuthenticator();
        $result = new ValidationResult();
        $member = $authenticator->authenticate(
            [
                'Email' => $email,
                'Password' => 'password',
            ],
            Controller::curr()->getRequest(),
            $result
        );
        $this->assertFalse($result->isValid());
        $this->assertNull($member);
        $this->assertCount(1, LoginAttempt::get());
        $attempt = LoginAttempt::get()->first();
        $this->assertEmpty($attempt->Email); // Doesn't store potentially sensitive data
        $this->assertEquals(sha1($email), $attempt->EmailHashed);
        $this->assertEquals(LoginAttempt::FAILURE, $attempt->Status);
    }

    public function testNonExistantMemberGetsLockedOut()
    {
        Security::config()->set('login_recording', true);
        Member::config()
            ->set('lock_out_after_incorrect_logins', 1)
            ->set('lock_out_delay_mins', 10);

        $email = 'notreal@example.com';
        $this->assertFalse(Member::get()->filter(array('Email' => $email))->exists());

        $authenticator = new MemberAuthenticator();
        $result = new ValidationResult();
        $member = $authenticator->authenticate(
            [
                'Email' => $email,
                'Password' => 'password',
            ],
            Controller::curr()->getRequest(),
            $result
        );

        $this->assertNull($member);
        $this->assertFalse($result->isValid());
        $member = new Member();
        $member->Email = $email;

        $this->assertTrue($member->isLockedOut());
        $this->assertFalse($member->canLogIn());
    }
}
