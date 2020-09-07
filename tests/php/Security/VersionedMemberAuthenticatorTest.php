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
use SilverStripe\Security\PasswordValidator;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * @skipUpgrade
 */
class VersionedMemberAuthenticatorTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected static $required_extensions = [
        Member::class => [
            Versioned::class
        ]
    ];

    public function setUp()
    {
        parent::setUp();

        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped("Versioned is required");
            return;
        }
    }

    protected function tearDown()
    {
        $this->logOut();
        parent::tearDown();
    }

    public function testAuthenticate()
    {
        $mockDate1 = '2010-01-01 10:00:00';
        $readingMode = sprintf('Archive.%s.Stage', $mockDate1);

        /** @var Member $member */
        $member = DBDatetime::withFixedNow($mockDate1, function () {
            $member = Member::create();
            $member->update([
                'FirstName' => 'Jane',
                'Surname' => 'Doe',
                'Email' => 'jane.doe@example.com'
            ]);
            $member->write();
            $member->changePassword('password', true);

            return $member;
        });

        $member->changePassword('new-password', true);

        /** @var ValidationResult $results */
        $results = Versioned::withVersionedMode(function () use ($readingMode) {
            Versioned::set_reading_mode($readingMode);
            $authenticator = new MemberAuthenticator();

            // Test correct login
            /** @var ValidationResult $message */
            $authenticator->authenticate(
                [
                    'Email' => 'jane.doe@example.com',
                    'Password' => 'password'
                ],
                Controller::curr()->getRequest(),
                $result
            );

            return $result;
        });

        $this->assertFalse(
            $results->isValid(),
            'Authenticate using old credentials fails even when using an old reading mode'
        );

        /** @var ValidationResult $results */
        $results = Versioned::withVersionedMode(function () use ($readingMode) {
            Versioned::set_reading_mode($readingMode);
            $authenticator = new MemberAuthenticator();

            // Test correct login
            /** @var ValidationResult $message */
            $authenticator->authenticate(
                [
                    'Email' => 'jane.doe@example.com',
                    'Password' => 'new-password'
                ],
                Controller::curr()->getRequest(),
                $result
            );

            return $result;
        });

        $this->assertTrue(
            $results->isValid(),
            'Authenticate using current credentials succeeds even when using an old reading mode'
        );
    }

    public function testAuthenticateAgainstLiveStage()
    {
        /** @var Member $member */
        $member = Member::create();
        $member->update([
            'FirstName' => 'Jane',
            'Surname' => 'Doe',
            'Email' => 'jane.doe@example.com'
        ]);
        $member->write();
        $member->changePassword('password', true);
        $member->publishSingle();

        $member->changePassword('new-password', true);

        /** @var ValidationResult $results */
        $results = Versioned::withVersionedMode(function () {
            Versioned::set_stage(Versioned::LIVE);
            $authenticator = new MemberAuthenticator();

            // Test correct login
            /** @var ValidationResult $message */
            $authenticator->authenticate(
                [
                    'Email' => 'jane.doe@example.com',
                    'Password' => 'password'
                ],
                Controller::curr()->getRequest(),
                $result
            );

            return $result;
        });

        $this->assertFalse(
            $results->isValid(),
            'Authenticate using "published" credentials fails when draft credentials have changed'
        );

        /** @var ValidationResult $results */
        $results = Versioned::withVersionedMode(function () {
            Versioned::set_stage(Versioned::LIVE);
            $authenticator = new MemberAuthenticator();

            // Test correct login
            /** @var ValidationResult $message */
            $authenticator->authenticate(
                [
                    'Email' => 'jane.doe@example.com',
                    'Password' => 'new-password'
                ],
                Controller::curr()->getRequest(),
                $result
            );

            return $result;
        });

        $this->assertTrue(
            $results->isValid(),
            'Authenticate using current credentials succeeds even when "published" credentials are different'
        );
    }
}
