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

	function setUp() {
		self::$originalVersionInfo = Deprecation::dump_settings();
		Deprecation::$notice_level = E_USER_NOTICE;
	}

	function tearDown() {
		Deprecation::restore_settings(self::$originalVersionInfo);
	}

	function testLesserVersionTriggersNoNotice() {
		Deprecation::notification_version('1.0.0');
		Deprecation::notice('2.0', 'Deprecation test failed');
	}

	/**
     * @expectedException PHPUnit_Framework_Error
	 */
	function testEqualVersionTriggersNotice() {
		Deprecation::notification_version('2.0.0');
		Deprecation::notice('2.0.0', 'Deprecation test passed');
	}

	function testBetaVersionDoesntTriggerNoticeWhenDeprecationDoesntSpecifyReleasenum() {
		Deprecation::notification_version('2.0.0-beta1');
		Deprecation::notice('2.0', 'Deprecation test failed');
		Deprecation::notice('2.0.0', 'Deprecation test failed');
	}

	/**
    * @expectedException PHPUnit_Framework_Error
	 */
	function testGreaterVersionTriggersNotice() {
		Deprecation::notification_version('3.0.0');
		Deprecation::notice('2.0', 'Deprecation test passed');
	}

	function testNonMatchingModuleNotifcationVersionDoesntAffectNotice() {
		Deprecation::notification_version('1.0.0');
		global $project;
		Deprecation::notification_version('3.0.0', $project);
		$this->callThatOriginatesFromFramework();
	}

	/**
    * @expectedException PHPUnit_Framework_Error
	 */
	function testMatchingModuleNotifcationVersionAffectsNotice() {
		Deprecation::notification_version('1.0.0');
		Deprecation::notification_version('3.0.0', FRAMEWORK_DIR);
		$this->callThatOriginatesFromFramework();
	}

	protected function callThatOriginatesFromFramework() {
		$this->assertEquals(DeprecationTest_Deprecation::get_module(), FRAMEWORK_DIR);
		Deprecation::notice('2.0', 'Deprecation test passed');
	}

	function testMethodNameCalculation() {
		$this->assertEquals(DeprecationTest_Deprecation::get_method(), 'DeprecationTest->testMethodNameCalculation');
	}

}
