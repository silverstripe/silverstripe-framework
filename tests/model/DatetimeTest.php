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

	function testSetNullAndZeroValues() {
		$date = DBField::create('SS_Datetime', '');
		$this->assertNull($date->getValue(), 'Empty string evaluates to NULL');

		$date = DBField::create('SS_Datetime', null);
		$this->assertNull($date->getValue(), 'NULL is set as NULL');

		$date = DBField::create('SS_Datetime', false);
		$this->assertNull($date->getValue(), 'Boolean FALSE evaluates to NULL');

		$date = DBField::create('SS_Datetime', '0');
		$this->assertEquals('1970-01-01 12:00:00', $date->getValue(), 'Zero is UNIX epoch time');

		$date = DBField::create('SS_Datetime', 0);
		$this->assertEquals('1970-01-01 12:00:00', $date->getValue(), 'Zero is UNIX epoch time');
	}

}
