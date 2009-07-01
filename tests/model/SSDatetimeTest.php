<?php
/**
 * Tests for {@link SSDatetime} class.
 * 
 * @todo Current date comparisons are slightly dodgy, as they only compare
 *  the current date (not hour, minute, second) and assume that the date
 *  doesn't switch throughout the test execution. This means tests might
 *  fail when run at 23:59:59.
 *
 * @package sapphire
 * @subpackage tests
 */
class SSDatetimeTest extends SapphireTest {
	function testNowWithSystemDate() {
		$systemDatetime = DBField::create('SSDatetime', date('Y-m-d H:i:s'));
		$nowDatetime = SSDatetime::now();
		
		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}
	
	function testNowWithMockDate() {
		// Test setting
		$mockDate = '2001-12-31 22:10:59';
		SSDatetime::set_mock_now($mockDate);
		$systemDatetime = DBField::create('SSDatetime', date('Y-m-d H:i:s'));
		$nowDatetime = SSDatetime::now();
		$this->assertNotEquals($systemDatetime->Date(), $nowDatetime->Date());
		$this->assertEquals($nowDatetime->getValue(), $mockDate);
		
		// Test clearing
		SSDatetime::clear_mock_now();
		$systemDatetime = DBField::create('SSDatetime', date('Y-m-d H:i:s'));
		$nowDatetime = SSDatetime::now();
		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}
}