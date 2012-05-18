<?php
/**
 * Tests for {@link SS_Datetime} class.
 * 
 * @todo Current date comparisons are slightly dodgy, as they only compare
 *  the current date (not hour, minute, second) and assume that the date
 *  doesn't switch throughout the test execution. This means tests might
 *  fail when run at 23:59:59.
 *
 * @package framework
 * @subpackage tests
 */
class SS_DatetimeTest extends SapphireTest {
	function testNowWithSystemDate() {
		$systemDatetime = DBField::create_field('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();
		
		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}
	
	function testNowWithMockDate() {
		// Test setting
		$mockDate = '2001-12-31 22:10:59';
		SS_Datetime::set_mock_now($mockDate);
		$systemDatetime = DBField::create_field('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();
		$this->assertNotEquals($systemDatetime->Date(), $nowDatetime->Date());
		$this->assertEquals($nowDatetime->getValue(), $mockDate);
		
		// Test clearing
		SS_Datetime::clear_mock_now();
		$systemDatetime = DBField::create_field('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();
		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}

	function testSetNullAndZeroValues() {
		$date = DBField::create_field('SS_Datetime', '');
		$this->assertNull($date->getValue(), 'Empty string evaluates to NULL');

		$date = DBField::create_field('SS_Datetime', null);
		$this->assertNull($date->getValue(), 'NULL is set as NULL');

		$date = DBField::create_field('SS_Datetime', false);
		$this->assertNull($date->getValue(), 'Boolean FALSE evaluates to NULL');

		$date = DBField::create_field('SS_Datetime', '0');
		$this->assertEquals('1970-01-01 00:00:00', $date->getValue(), 'String zero is UNIX epoch time');

		$date = DBField::create_field('SS_Datetime', 0);
		$this->assertEquals('1970-01-01 00:00:00', $date->getValue(), 'Numeric zero is UNIX epoch time');
	}
	
	function testExtendedDateTimes() {
		$date = DBField::create_field('SS_Datetime', '1500-10-10 15:32:24');
		$this->assertEquals('10 Oct 1500 15 32 24', $date->Format('d M Y H i s'));
		
		$date = DBField::create_field('SS_Datetime', '3000-10-10 15:32:24');
		$this->assertEquals('10 Oct 3000 15 32 24', $date->Format('d M Y H i s'));
	}
	
	function testNice() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('31/12/2001 10:10pm', $date->Nice());
	}
	
	function testNice24() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('31/12/2001 22:10', $date->Nice24());
	}
	
	function testDate() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('31/12/2001', $date->Date());
	}
	
	function testTime() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('10:10pm', $date->Time());
	}
	
	function testTime24() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('22:10', $date->Time24());
	}
	
	function testURLDateTime(){
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('2001-12-31%2022:10:59', $date->URLDateTime());
	}

}
