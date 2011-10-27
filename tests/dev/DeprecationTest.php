<?php

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
		Deprecation::notice('Deprecation test failed', '2.0.0');
	}

	/**
    * @expectedException PHPUnit_Framework_Error_Notice
	 */
	function testEqualVersionTriggersNotice() {
		Deprecation::notification_version('2.0.0');
		Deprecation::notice('Deprecation test passed', '2.0.0');
	}

	/**
    * @expectedException PHPUnit_Framework_Error_Notice
	 */
	function testGreaterVersionTriggersNotice() {
		Deprecation::notification_version('3.0.0');
		Deprecation::notice('Deprecation test passed', '2.0.0');
	}

	function testNonMatchingModuleNotifcationVersionDoesntAffectNotice() {
		Deprecation::notification_version('1.0.0');
		Deprecation::notification_version('3.0.0', 'mysite');
		Deprecation::notice('Deprecation test failed', '2.0.0');
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
		Deprecation::notice('Deprecation test passed', '2.0.0');
	}
}
