<?php
/**
 * Tests for {@link SS_Datetime} class.
 * 
 * @todo Current date comparisons are slightly dodgy, as they only compare
 *  the current date (not hour, minute, second) and assume that the date
 *  doesn't switch throughout the test execution. This means tests might
 *  fail when run at 23:59:59.
 *
 * @package sapphire
 * @subpackage tests
 */
class SS_DatetimeTest extends SapphireTest {
	function testNowWithSystemDate() {
		$systemDatetime = DBField::create('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();
		
		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}
	
	function testNowWithMockDate() {
		// Test setting
		$mockDate = '2001-12-31 22:10:59';
		SS_Datetime::set_mock_now($mockDate);
		$systemDatetime = DBField::create('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();
		$this->assertNotEquals($systemDatetime->Date(), $nowDatetime->Date());
		$this->assertEquals($nowDatetime->getValue(), $mockDate);
		
		// Test clearing
		SS_Datetime::clear_mock_now();
		$systemDatetime = DBField::create('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();
		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}
}