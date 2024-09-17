<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\SessionManager\Models\LoginSession;
use SilverStripe\Dev\Deprecation;

class RememberLoginHashTest extends SapphireTest
{
    protected static $fixture_file = 'RememberLoginHashTest.yml';

    /** @var RememberLoginHash[]  */
    private $loginHash = [];

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Member $main */
        $main = $this->objFromFixture(Member::class, 'main');

        /** @var Member $secondary */
        $secondary = $this->objFromFixture(Member::class, 'secondary');

        $this->loginHash = [
            'current' => RememberLoginHash::generate($main),
            'other' => RememberLoginHash::generate($main),
            'secondary' => RememberLoginHash::generate($secondary),
        ];
    }

    public function clearScenarios()
    {
        return [
            'logout across devices' => [true, 'current', ['secondary'], ['current', 'other']],
            'logout across devices on non-persistent session' => [true, false, ['secondary'], ['current', 'other']],
            'logout single device' => [false, 'current', ['secondary', 'other'], ['current']],
            'logout single device on non-persistent session' => [false, false, ['secondary', 'current', 'other'], []],
        ];
    }

    /**
     * @param bool $logoutAcrossDevices
     * @param mixed $deviceId
     * @param array $expected
     * @param array $unexpected
     * @dataProvider clearScenarios
     */
    public function testClear(bool $logoutAcrossDevices, $deviceId, array $expected, array $unexpected)
    {
        // If session-manager module is installed then logout_across_devices is modified so skip
        if (class_exists(LoginSession::class)) {
            $this->markTestSkipped();
        }
        RememberLoginHash::config()->set('logout_across_devices', $logoutAcrossDevices);

        RememberLoginHash::clear(
            $this->objFromFixture(Member::class, 'main'),
            $deviceId ? $this->loginHash[$deviceId]->DeviceID : null
        );

        foreach ($expected as $key) {
            $ID = $this->loginHash[$key]->ID;
            $this->assertNotEmpty(
                RememberLoginHash::get()->byID($ID),
                "$key $ID RememberLoginHash is found"
            );
        }

        foreach ($unexpected as $key) {
            $ID = $this->loginHash[$key]->ID;
            $this->assertEmpty(
                RememberLoginHash::get()->byID($ID),
                "$key RememberLoginHash has been removed"
            );
        }
    }

    public function testGetSetLogoutAcrossDevices()
    {
        // If session-manager module is installed then logout_across_devices is modified so skip
        if (class_exists(LoginSession::class)) {
            $this->markTestSkipped();
        }
        // set config directly
        RememberLoginHash::config()->set('logout_across_devices', true);
        $this->assertTrue(RememberLoginHash::getLogoutAcrossDevices());
        RememberLoginHash::config()->set('logout_across_devices', false);
        $this->assertFalse(RememberLoginHash::getLogoutAcrossDevices());
        // override using public API
        RememberLoginHash::setLogoutAcrossDevices(true);
        $this->assertTrue(RememberLoginHash::getLogoutAcrossDevices());
        RememberLoginHash::setLogoutAcrossDevices(false);
        $this->assertFalse(RememberLoginHash::getLogoutAcrossDevices());
    }

    /**
     * @dataProvider provideRenew
     * @param bool $replaceToken
     */
    public function testRenew($replaceToken)
    {
        // If session-manager module is installed it expects an active request during renewal
        if (class_exists(LoginSession::class)) {
            $this->markTestSkipped();
        }

        $member = $this->objFromFixture(Member::class, 'main');

        Deprecation::withSuppressedNotice(
            fn() => RememberLoginHash::config()->set('replace_token_during_session_renewal', $replaceToken)
        );

        $hash = RememberLoginHash::generate($member);
        $oldToken = $hash->getToken();
        $oldHash = $hash->Hash;

        // Fetch the token from the DB - otherwise we still have the token from when this was originally created
        $storedHash = RememberLoginHash::get()->find('ID', $hash->ID);

        Deprecation::withSuppressedNotice(fn() => $storedHash->renew());

        if ($replaceToken) {
            $this->assertNotEquals($oldToken, $storedHash->getToken());
            $this->assertNotEquals($oldHash, $storedHash->Hash);
        } else {
            $this->assertEmpty($storedHash->getToken());
            $this->assertEquals($oldHash, $storedHash->Hash);
        }
    }

    public function provideRenew(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
