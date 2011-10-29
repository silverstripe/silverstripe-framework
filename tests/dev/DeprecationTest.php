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
     * @expectedException PHPUnit_Framework_Error_Notice
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
    * @expectedException PHPUnit_Framework_Error_Notice
	 */
	function testGreaterVersionTriggersNotice() {
		Deprecation::notification_version('3.0.0');
		Deprecation::notice('2.0', 'Deprecation test passed');
	}

	function testNonMatchingModuleNotifcationVersionDoesntAffectNotice() {
		Deprecation::notification_version('1.0.0');
		Deprecation::notification_version('3.0.0', 'mysite');
		$this->callThatOriginatesFromSapphire();
	}

	/**
    * @expectedException PHPUnit_Framework_Error_Notice
	 */
	function testMatchingModuleNotifcationVersionAffectsNotice() {
		Deprecation::notification_version('1.0.0');
		Deprecation::notification_version('3.0.0', 'sapphire');
		$this->callThatOriginatesFromSapphire();
	}

	protected function callThatOriginatesFromSapphire() {
		$this->assertEquals(DeprecationTest_Deprecation::get_module(), 'sapphire');
		Deprecation::notice('2.0', 'Deprecation test passed');
	}

	function testMethodNameCalculation() {
		$this->assertEquals(DeprecationTest_Deprecation::get_method(), 'DeprecationTest->testMethodNameCalculation');
	}

}
