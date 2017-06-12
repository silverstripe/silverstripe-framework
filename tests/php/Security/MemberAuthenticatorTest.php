<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\MemberAuthenticator\CMSMemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\CMSMemberLoginForm;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\DefaultAdminService;

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
        DefaultAdminService::clearDefaultAdmin();
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
            ->handleRequest(new HTTPRequest('get', '/'));

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
}
