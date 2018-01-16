<?php

namespace SilverStripe\Dev\Tests;

use PHPUnit_Framework_Error;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\TestDeprecation;

class DeprecationTest extends SapphireTest
{

    static $originalVersionInfo;

    protected function setUp()
    {
        parent::setUp();

        self::$originalVersionInfo = Deprecation::dump_settings();
        Deprecation::$notice_level = E_USER_NOTICE;
        Deprecation::set_enabled(true);
    }

    protected function tearDown()
    {
        Deprecation::restore_settings(self::$originalVersionInfo);
        parent::tearDown();
    }

    public function testLesserVersionTriggersNoNotice()
    {
        Deprecation::notification_version('1.0.0');
        $this->assertNull(Deprecation::notice('2.0', 'Deprecation test failed'));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testEqualVersionTriggersNotice()
    {
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Deprecation test passed');
    }

    public function testBetaVersionDoesntTriggerNoticeWhenDeprecationDoesntSpecifyReleasenum()
    {
        Deprecation::notification_version('2.0.0-beta1');
        $this->assertNull(Deprecation::notice('2.0', 'Deprecation test failed'));
        $this->assertNull(Deprecation::notice('2.0.0', 'Deprecation test failed'));
    }

    /**
    * @expectedException PHPUnit_Framework_Error
     */
    public function testGreaterVersionTriggersNotice()
    {
        Deprecation::notification_version('3.0.0');
        Deprecation::notice('2.0', 'Deprecation test passed');
    }

    public function testNonMatchingModuleNotifcationVersionDoesntAffectNotice()
    {
        Deprecation::notification_version('1.0.0');
        Deprecation::notification_version('3.0.0', 'my-unrelated-module');
        $this->callThatOriginatesFromFramework();
    }

    /**
    * @expectedException PHPUnit_Framework_Error
     */
    public function testMatchingModuleNotifcationVersionAffectsNotice()
    {
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

    /**
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage DeprecationTest->testScopeMethod is deprecated. Method scope
     */
    public function testScopeMethod()
    {
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Method scope', Deprecation::SCOPE_METHOD);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage DeprecationTest is deprecated. Class scope
     */
    public function testScopeClass()
    {
        Deprecation::notification_version('2.0.0');
        Deprecation::notice('2.0.0', 'Class scope', Deprecation::SCOPE_CLASS);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Global scope
     */
    public function testScopeGlobal()
    {
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
