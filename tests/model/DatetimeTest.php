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
	public function testNowWithSystemDate() {
		$systemDatetime = DBField::create_field('SS_Datetime', date('Y-m-d H:i:s'));
		$nowDatetime = SS_Datetime::now();

		$this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
	}

	public function testNowWithMockDate() {
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

	public function testSetNullAndZeroValues() {
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

	public function testExtendedDateTimes() {
		$date = DBField::create_field('SS_Datetime', '1500-10-10 15:32:24');
		$this->assertEquals('10 Oct 1500 15 32 24', $date->Format('d M Y H i s'));

		$date = DBField::create_field('SS_Datetime', '3000-10-10 15:32:24');
		$this->assertEquals('10 Oct 3000 15 32 24', $date->Format('d M Y H i s'));
	}

	public function testNice() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('31/12/2001 10:10pm', $date->Nice());
	}

	public function testNice24() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('31/12/2001 22:10', $date->Nice24());
	}

	public function testDate() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('31/12/2001', $date->Date());
	}

	public function testTime() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('10:10pm', $date->Time());
	}

	public function testTime24() {
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('22:10', $date->Time24());
	}

	public function testURLDateTime(){
		$date = DBField::create_field('SS_Datetime', '2001-12-31 22:10:59');
		$this->assertEquals('2001-12-31%2022:10:59', $date->URLDateTime());
	}

	public function testAgoInPast() {
		SS_Datetime::set_mock_now('2000-12-31 12:00:00');

		$this->assertEquals(
			'10 years ago',
			DBField::create_field('SS_Datetime', '1990-12-31 12:00:00')->Ago(),
			'Exact past match on years'
		);

		$this->assertEquals(
			'10 years ago',
			DBField::create_field('SS_Datetime', '1990-12-30 12:00:00')->Ago(),
			'Approximate past match on years'
		);

		$this->assertEquals(
			'1 year ago', 
			DBField::create_field('SS_Datetime', '1999-12-30 12:00:12')->Ago(true, 1),
			'Approximate past match in singular, significance=1'
		);

		$this->assertEquals(
			'12 months ago', 
			DBField::create_field('SS_Datetime', '1999-12-30 12:00:12')->Ago(),
			'Approximate past match in singular'
		);

		$this->assertEquals(
			'50 mins ago',
			DBField::create_field('SS_Datetime', '2000-12-31 11:10:11')->Ago(),
			'Approximate past match on minutes'
		);

		$this->assertEquals(
			'59 secs ago',
			DBField::create_field('SS_Datetime', '2000-12-31 11:59:01')->Ago(),
			'Approximate past match on seconds'
		);

		$this->assertEquals(
			'less than a minute ago',
			DBField::create_field('SS_Datetime', '2000-12-31 11:59:01')->Ago(false),
			'Approximate past match on seconds with $includeSeconds=false'
		);
		
		$this->assertEquals(
			'1 min ago',
			DBField::create_field('SS_Datetime', '2000-12-31 11:58:50')->Ago(false),
			'Test between 1 and 2 minutes with includeSeconds=false'
		);
		
		$this->assertEquals(
			'70 secs ago',
			DBField::create_field('SS_Datetime', '2000-12-31 11:58:50')->Ago(true),
			'Test between 1 and 2 minutes with includeSeconds=true'
		);

		$this->assertEquals(
			'4 mins ago', 
			DBField::create_field('SS_Datetime', '2000-12-31 11:55:50')->Ago(),
			'Past match on minutes'
		);

		$this->assertEquals(
			'1 hour ago', 
			DBField::create_field('SS_Datetime', '2000-12-31 10:50:58')->Ago(true, 1),
			'Past match on hours, significance=1'
		);

		$this->assertEquals(
			'3 hours ago', 
			DBField::create_field('SS_Datetime', '2000-12-31 08:50:58')->Ago(),
			'Past match on hours'
		);

		SS_Datetime::clear_mock_now();
	}

	public function testAgoInFuture() {
		SS_Datetime::set_mock_now('2000-12-31 00:00:00');

		$this->assertEquals(
			'in 10 years',
			DBField::create_field('SS_Datetime', '2010-12-31 12:00:00')->Ago(),
			'Exact past match on years'
		);

		$this->assertEquals(
			'in 1 hour', 
			DBField::create_field('SS_Datetime', '2000-12-31 1:01:05')->Ago(true, 1),
			'Approximate past match on minutes, significance=1'
		);

		$this->assertEquals(
			'in 61 mins', 
			DBField::create_field('SS_Datetime', '2000-12-31 1:01:05')->Ago(),
			'Approximate past match on minutes'
		);

		SS_Datetime::clear_mock_now();
	}

	public function testFormatFromSettings() {

		$memberID = $this->logInWithPermission();
		$member = DataObject::get_by_id('Member', $memberID);
		$member->DateFormat = 'dd/MM/YYYY';
		$member->TimeFormat = 'hh:mm:ss';
		$member->write();

		$fixtures = array(
			'2000-12-31 10:11:01' => '31/12/2000 10:11:01',
			'2000-12-31 1:11:01' => '31/12/2000 01:11:01',
			'12/12/2000 1:11:01' => '12/12/2000 01:11:01',
			'2000-12-31' => '31/12/2000 12:00:00',
			'2014-04-01 10:11:01' => '01/04/2014 10:11:01',
			'10:11:01' => date('d/m/Y').' 10:11:01'
		);

		foreach($fixtures as $from => $to) {
			$date = DBField::create_field('SS_Datetime', $from);
			// With member
			$this->assertEquals($to, $date->FormatFromSettings($member));
			// Without member
			$this->assertEquals($to, $date->FormatFromSettings());
		}
	}

}
