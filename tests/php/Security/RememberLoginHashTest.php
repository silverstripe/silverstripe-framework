<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\RememberLoginHash;

class RememberLoginHashTest extends SapphireTest
{
    protected static $fixture_file = 'RememberLoginHashTest.yml';

    /** @var RememberLoginHash[]  */
    private $loginHash = [];

    protected function setUp()
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
     * @param string $deviceId
     * @param array $expected
     * @param array $unexpected
     * @dataProvider clearScenarios
     */
    public function testClear(bool $logoutAcrossDevices, string $deviceId, array $expected, array $unexpected)
    {
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
}
