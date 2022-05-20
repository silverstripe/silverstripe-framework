<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\TestDeprecation;

class DeprecationTest extends SapphireTest
{

    static $originalVersionInfo;

    protected function setUp(): void
    {
        parent::setUp();

        self::$originalVersionInfo = Deprecation::dump_settings();
        Deprecation::$notice_level = E_USER_NOTICE;
        Deprecation::set_enabled(true);
    }

    protected function tearDown(): void
    {
        Deprecation::restore_settings(self::$originalVersionInfo);
        parent::tearDown();
    }

    public function testLesserVersionTriggersNoNotice()
    {
        Deprecation::notification_version('1.0.0');
        $this->assertNull(Deprecation::notice('2.0', 'Deprecation test failed'));
    }

    public function testEqualVersionTriggersNotice()
    {
        $this->expectError();
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Deprecation test passed');
    }

    public function testBetaVersionDoesntTriggerNoticeWhenDeprecationDoesntSpecifyReleasenum()
    {
        Deprecation::notification_version('2.0.0-beta1');
        $this->assertNull(Deprecation::notice('2.0', 'Deprecation test failed'));
        $this->assertNull(Deprecation::notice('2.0.0', 'Deprecation test failed'));
    }

    public function testGreaterVersionTriggersNotice()
    {
        $this->expectError();
        Deprecation::notification_version('3.0.0');
        Deprecation::notice('2.0', 'Deprecation test passed');
    }

    public function testNonMatchingModuleNotifcationVersionDoesntAffectNotice()
    {
        Deprecation::notification_version('1.0.0');
        Deprecation::notification_version('3.0.0', 'my-unrelated-module');
        $this->callThatOriginatesFromFramework();
    }

    public function testMatchingModuleNotifcationVersionAffectsNotice()
    {
        $this->expectError();
        Deprecation::notification_version('1.0.0');
        Deprecation::notification_version('3.0.0', 'silverstripe/framework');
        $this->callThatOriginatesFromFramework();
    }

    public function testMethodNameCalculation()
    {
        $this->assertEquals(
            TestDeprecation::get_method(),
            static::class . '->testMethodNameCalculation'
        );
    }

    public function testScopeMethod()
    {
        $this->expectError();
        $this->expectErrorMessage('DeprecationTest->testScopeMethod is deprecated. Method scope');
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Method scope', Deprecation::SCOPE_METHOD);
    }

    public function testScopeClass()
    {
        $this->expectError();
        $this->expectErrorMessage('DeprecationTest is deprecated. Class scope');
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Class scope', Deprecation::SCOPE_CLASS);
    }

    public function testScopeGlobal()
    {
        $this->expectError();
        $this->expectErrorMessage('Global scope');
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Global scope', Deprecation::SCOPE_GLOBAL);
    }

    protected function callThatOriginatesFromFramework()
    {
        $module = TestDeprecation::get_module();
        $this->assertNotNull($module);
        $this->assertEquals('silverstripe/framework', $module->getName());
        $this->assertNull(Deprecation::notice('2.0', 'Deprecation test passed'));
    }
}
