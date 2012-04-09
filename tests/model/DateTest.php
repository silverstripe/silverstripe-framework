<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DateTest extends SapphireTest {
	
	protected $originalTZ;

	function setUp() {
		// Set timezone to support timestamp->date conversion.
		$this->originalTZ = date_default_timezone_get();
		date_default_timezone_set('Pacific/Auckland');
		parent::setUp();
	}

	function tearDown() {
		date_default_timezone_set($this->originalTZ);
		parent::tearDown();
	}

	function testNiceDate() {
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
	
	function testLongDate() {
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

	function testSetNullAndZeroValues() {
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

	function testDayOfMonth() {
		$date = DBField::create_field('Date', '2000-10-10');
		$this->assertEquals('10', $date->DayOfMonth());
		$this->assertEquals('10th', $date->DayOfMonth(true));

		$range = $date->RangeString(DBField::create_field('Date', '2000-10-20'));
		$this->assertEquals('10 - 20 Oct 2000', $range);
		$range = $date->RangeString(DBField::create_field('Date', '2000-10-20'), true);
		$this->assertEquals('10th - 20th Oct 2000', $range);
	}
}
