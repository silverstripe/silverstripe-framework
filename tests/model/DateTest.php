<?php
/**
 * @package framework
 * @subpackage tests
 */
class DateTest extends SapphireTest {
	
	protected $originalTZ;

	public function setUp() {
		// Set timezone to support timestamp->date conversion.
		$this->originalTZ = date_default_timezone_get();
		date_default_timezone_set('Pacific/Auckland');
		parent::setUp();
	}

	public function tearDown() {
		date_default_timezone_set($this->originalTZ);
		parent::tearDown();
	}

	public function testNiceDate() {
		$this->assertEquals('31/03/2008', DBField::create_field('Date', 1206968400)->Nice(),
			"Date->Nice() works with timestamp integers"
		);
		$this->assertEquals('30/03/2008', DBField::create_field('Date', 1206882000)->Nice(),
			"Date->Nice() works with timestamp integers"
		);
		$this->assertEquals('31/03/2008', DBField::create_field('Date', '1206968400')->Nice(),
			"Date->Nice() works with timestamp strings"
		);
		$this->assertEquals('30/03/2008', DBField::create_field('Date', '1206882000')->Nice(),
			"Date->Nice() works with timestamp strings"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '4/3/03')->Nice(),
			"Date->Nice() works with D/M/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '04/03/03')->Nice(),
			"Date->Nice() works with DD/MM/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '4/3/03')->Nice(),
			"Date->Nice() works with D/M/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '4/03/03')->Nice(),
			"Date->Nice() works with D/M/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '4/3/2003')->Nice(),
			"Date->Nice() works with D/M/YYYY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '4-3-2003')->Nice(),
			"Date->Nice() works with D-M-YYYY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '2003-03-04')->Nice(),
			"Date->Nice() works with YYYY-MM-DD format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '04/03/2003')->Nice(),
			"Date->Nice() works with DD/MM/YYYY format"
		);
		$this->assertEquals('04/03/2003', DBField::create_field('Date', '04-03-2003')->Nice(),
			"Date->Nice() works with DD/MM/YYYY format"
		);
	}
	
	public function testNiceUS(){
		$this->assertEquals('03/31/2008', DBField::create_field('Date', 1206968400)->NiceUs(),
			"Date->NiceUs() works with timestamp integers"
		);
	}
	
	public function testYear(){
		$this->assertEquals('2008', DBField::create_field('Date', 1206968400)->Year(),
			"Date->Year() works with timestamp integers"
		);
	}
	
	public function testDay(){
		$this->assertEquals('Monday', DBField::create_field('Date', 1206968400)->Day(),
			"Date->Day() works with timestamp integers"
		);
	}
	
	public function testMonth(){
		$this->assertEquals('March', DBField::create_field('Date', 1206968400)->Month(),
			"Date->Month() works with timestamp integers"
		);
	}
	
	public function testShortMonth(){
		$this->assertEquals('Mar', DBField::create_field('Date', 1206968400)->ShortMonth(),
			"Date->ShortMonth() works with timestamp integers"
		);
	}
	
	public function testLongDate() {
		$this->assertEquals('31 March 2008', DBField::create_field('Date', 1206968400)->Long(),
			"Date->Long() works with numeric timestamp"
		);
		$this->assertEquals('31 March 2008', DBField::create_field('Date', '1206968400')->Long(),
			"Date->Long() works with string timestamp"
		);
		$this->assertEquals('30 March 2008', DBField::create_field('Date', 1206882000)->Long(),
			"Date->Long() works with numeric timestamp"
		);
		$this->assertEquals('30 March 2008', DBField::create_field('Date', '1206882000')->Long(),
			"Date->Long() works with numeric timestamp"
		);
		$this->assertEquals('3 April 2003', DBField::create_field('Date', '2003-4-3')->Long(),
			"Date->Long() works with YYYY-M-D"
		);
		$this->assertEquals('3 April 2003', DBField::create_field('Date', '3/4/2003')->Long(),
			"Date->Long() works with D/M/YYYY"
		);
	}
	
	public function testFull(){
		$this->assertEquals('31 Mar 2008', DBField::create_field('Date', 1206968400)->Full(),
			"Date->Full() works with timestamp integers"
		);
	}

	public function testSetNullAndZeroValues() {
		$date = DBField::create_field('Date', '');
		$this->assertNull($date->getValue(), 'Empty string evaluates to NULL');

		$date = DBField::create_field('Date', null);
		$this->assertNull($date->getValue(), 'NULL is set as NULL');

		$date = DBField::create_field('Date', false);
		$this->assertNull($date->getValue(), 'Boolean FALSE evaluates to NULL');

		$date = DBField::create_field('Date', array());
		$this->assertNull($date->getValue(), 'Empty array evaluates to NULL');

		$date = DBField::create_field('Date', '0');
		$this->assertEquals('1970-01-01', $date->getValue(), 'Zero is UNIX epoch date');

		$date = DBField::create_field('Date', 0);
		$this->assertEquals('1970-01-01', $date->getValue(), 'Zero is UNIX epoch date');
	}

	public function testDayOfMonth() {
		$date = DBField::create_field('Date', '2000-10-10');
		$this->assertEquals('10', $date->DayOfMonth());
		$this->assertEquals('10th', $date->DayOfMonth(true));

		$range = $date->RangeString(DBField::create_field('Date', '2000-10-20'));
		$this->assertEquals('10 - 20 Oct 2000', $range);
		$range = $date->RangeString(DBField::create_field('Date', '2000-10-20'), true);
		$this->assertEquals('10th - 20th Oct 2000', $range);
	}

	public function testExtendedDates() {
		$date = DBField::create_field('Date', '1800-10-10');
		$this->assertEquals('10 Oct 1800', $date->Format('d M Y'));

		$date = DBField::create_field('Date', '1500-10-10');
		$this->assertEquals('10 Oct 1500', $date->Format('d M Y'));

		$date = DBField::create_field('Date', '3000-4-3');
		$this->assertEquals('03 Apr 3000', $date->Format('d M Y'));
	}

}
