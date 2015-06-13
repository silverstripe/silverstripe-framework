<?php

class DeprecationTest_Deprecation extends Deprecation {
	public static function get_module() {
		return self::get_calling_module_from_trace(debug_backtrace(0));
	}

	public static function get_method() {
		return self::get_called_method_from_trace(debug_backtrace(0));
	}
}

class DeprecationTest extends SapphireTest {

	static $originalVersionInfo;

	public function setUp() {
		parent::setUp();
		
		self::$originalVersionInfo = Deprecation::dump_settings();
		Deprecation::$notice_level = E_USER_NOTICE;
		Deprecation::set_enabled(true);
	}

	public function tearDown() {
		Deprecation::restore_settings(self::$originalVersionInfo);
		parent::tearDown();
	}

	public function testLesserVersionTriggersNoNotice() {
		Deprecation::notification_version('1.0.0');
		$this->assertNull(Deprecation::notice('2.0', 'Deprecation test failed'));
	}

	/**
     * @expectedException PHPUnit_Framework_Error
	 */
	public function testEqualVersionTriggersNotice() {
		Deprecation::notification_version('2.0.0');
		Deprecation::notice('2.0.0', 'Deprecation test passed');
	}

	public function testBetaVersionDoesntTriggerNoticeWhenDeprecationDoesntSpecifyReleasenum() {
		Deprecation::notification_version('2.0.0-beta1');
		$this->assertNull(Deprecation::notice('2.0', 'Deprecation test failed'));
		$this->assertNull(Deprecation::notice('2.0.0', 'Deprecation test failed'));
	}

	/**
    * @expectedException PHPUnit_Framework_Error
	 */
	public function testGreaterVersionTriggersNotice() {
		Deprecation::notification_version('3.0.0');
		Deprecation::notice('2.0', 'Deprecation test passed');
	}

	public function testNonMatchingModuleNotifcationVersionDoesntAffectNotice() {
		Deprecation::notification_version('1.0.0');
		global $project;
		Deprecation::notification_version('3.0.0', $project);
		$this->callThatOriginatesFromFramework();
	}

	/**
    * @expectedException PHPUnit_Framework_Error
	 */
	public function testMatchingModuleNotifcationVersionAffectsNotice() {
		Deprecation::notification_version('1.0.0');
		Deprecation::notification_version('3.0.0', FRAMEWORK_DIR);
		$this->callThatOriginatesFromFramework();
	}

	public function testMethodNameCalculation() {
		$this->assertEquals(DeprecationTest_Deprecation::get_method(), 'DeprecationTest->testMethodNameCalculation');
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionMessage DeprecationTest->testScopeMethod is deprecated. Method scope
	 */
	public function testScopeMethod() {
		Deprecation::notification_version('2.0.0');
		Deprecation::notice('2.0.0', 'Method scope', Deprecation::SCOPE_METHOD);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionMessage DeprecationTest is deprecated. Class scope
	 */
	public function testScopeClass() {
		Deprecation::notification_version('2.0.0');
		Deprecation::notice('2.0.0', 'Class scope', Deprecation::SCOPE_CLASS);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionMessage Global scope
	 */
	public function testScopeGlobal() {
		Deprecation::notification_version('2.0.0');
		Deprecation::notice('2.0.0', 'Global scope', Deprecation::SCOPE_GLOBAL);
	}

	protected function callThatOriginatesFromFramework() {
		$this->assertEquals(DeprecationTest_Deprecation::get_module(), FRAMEWORK_DIR);
		$this->assertNull(Deprecation::notice('2.0', 'Deprecation test passed'));
	}

}
