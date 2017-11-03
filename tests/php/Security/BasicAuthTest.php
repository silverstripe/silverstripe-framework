<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Director;
use SilverStripe\Security\Tests\BasicAuthTest\ControllerSecuredWithoutPermission;
use SilverStripe\Security\Tests\BasicAuthTest\ControllerSecuredWithPermission;

/**
 * @skipUpgrade
 */
class BasicAuthTest extends FunctionalTest
{

    protected static $original_unique_identifier_field;

    protected static $fixture_file = 'BasicAuthTest.yml';

    protected static $extra_controllers = [
        ControllerSecuredWithPermission::class,
        ControllerSecuredWithoutPermission::class,
    ];

    protected function setUp()
    {
        parent::setUp();

        // Fixtures assume Email is the field used to identify the log in identity
        Member::config()->set('unique_identifier_field', 'Email');
        Security::force_database_is_ready(true); // Prevents Member test subclasses breaking ready test
        Member::config()->set('lock_out_after_incorrect_logins', 10);

        // Temp disable is_cli() exemption for tests
        BasicAuth::config()->set('ignore_cli', false);

        // Reset statics
        BasicAuthTest\ControllerSecuredWithPermission::$index_called = false;
        BasicAuthTest\ControllerSecuredWithPermission::$post_init_called = false;
    }

    public function testBasicAuthEnabledWithoutLogin()
    {
        $response = Director::test('BasicAuthTest_ControllerSecuredWithPermission');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testBasicAuthDoesntCallActionOrFurtherInitOnAuthFailure()
    {
        Director::test('BasicAuthTest_ControllerSecuredWithPermission');
        $this->assertFalse(BasicAuthTest\ControllerSecuredWithPermission::$index_called);
        $this->assertFalse(BasicAuthTest\ControllerSecuredWithPermission::$post_init_called);

        $headers = [
            'PHP_AUTH_USER' => 'user-in-mygroup@test.com',
            'PHP_AUTH_PW' => 'test',
        ];
        Director::test('BasicAuthTest_ControllerSecuredWithPermission', [], [], null, null, $headers);
        $this->assertTrue(BasicAuthTest\ControllerSecuredWithPermission::$index_called);
        $this->assertTrue(BasicAuthTest\ControllerSecuredWithPermission::$post_init_called);
    }

    public function testBasicAuthEnabledWithPermission()
    {
        $headers = [
            'PHP_AUTH_USER' => 'user-in-mygroup@test.com',
            'PHP_AUTH_PW' => 'wrongpassword',
        ];
        $response = Director::test('BasicAuthTest_ControllerSecuredWithPermission', [], [], null, null, $headers);
        $this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');

        $headers = [
            'PHP_AUTH_USER' => 'user-without-groups@test.com',
            'PHP_AUTH_PW' => 'test',
        ];
        $response = Director::test('BasicAuthTest_ControllerSecuredWithPermission', [], [], null, null, $headers);
        $this->assertEquals(401, $response->getStatusCode(), 'Valid user without required permission has no access');

        $headers = [
            'PHP_AUTH_USER' => 'user-in-mygroup@test.com',
            'PHP_AUTH_PW' => 'test',
        ];
        $response = Director::test('BasicAuthTest_ControllerSecuredWithPermission', [], [], null, null, $headers);
        $this->assertEquals(200, $response->getStatusCode(), 'Valid user with required permission has access');
    }

    public function testBasicAuthEnabledWithoutPermission()
    {
        $headers = [
            'PHP_AUTH_USER' => 'user-without-groups@test.com',
            'PHP_AUTH_PW' => 'wrongpassword',
        ];
        $response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission', [], [], null, null, $headers);
        $this->assertEquals(401, $response->getStatusCode(), 'Invalid users dont have access');

        $headers = [
            'PHP_AUTH_USER' => 'user-without-groups@test.com',
            'PHP_AUTH_PW' => 'test',
        ];
        $response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission', [], [], null, null, $headers);
        $this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');

        $headers = [
            'PHP_AUTH_USER' => 'user-in-mygroup@test.com',
            'PHP_AUTH_PW' => 'test',
        ];
        $response = Director::test('BasicAuthTest_ControllerSecuredWithoutPermission', [], [], null, null, $headers);
        $this->assertEquals(200, $response->getStatusCode(), 'All valid users have access');
    }

    public function testBasicAuthFailureIncreasesFailedLoginCount()
    {
        // Prior to login
        $check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
        $this->assertEquals(0, $check->FailedLoginCount);

        // First failed attempt
        $headers = [
            'PHP_AUTH_USER' => 'failedlogin@test.com',
            'PHP_AUTH_PW' => 'test',
        ];
        Director::test('BasicAuthTest_ControllerSecuredWithoutPermission', [], [], null, null, $headers);
        $check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
        $this->assertEquals(1, $check->FailedLoginCount);

        // Second failed attempt
        $headers['PHP_AUTH_PW'] = 'testwrong';
        Director::test('BasicAuthTest_ControllerSecuredWithoutPermission', [], [], null, null, $headers);
        $check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
        $this->assertEquals(2, $check->FailedLoginCount);

        // successful basic auth should reset failed login count
        $headers['PHP_AUTH_PW'] = 'Password';
        Director::test('BasicAuthTest_ControllerSecuredWithoutPermission', [], [], null, null, $headers);
        $check = Member::get()->filter('Email', 'failedlogin@test.com')->first();
        $this->assertEquals(0, $check->FailedLoginCount);
    }
}
