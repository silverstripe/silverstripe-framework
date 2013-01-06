<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DateTest extends SapphireTest {
	
	protected $originalTZ;
	
	function setUp() {
		// Set timezone to support timestamp->date conversion.
		// We can't use date_default_timezone_set() as its not supported prior to PHP 5.2
		$this->originalTZ = date_default_timezone_get();
		date_default_timezone_set('Pacific/Auckland');
		
		parent::setUp();
	}
	
	function tearDown() {
		date_default_timezone_set($this->originalTZ);
		
		parent::tearDown();
	}
	
	function testNiceDate() {
		$this->assertEquals('01/04/2008', DBField::create('Date', 1206968400)->Nice(),
			"Date->Nice() works with timestamp integers"
		);
		$this->assertEquals('31/03/2008', DBField::create('Date', 1206882000)->Nice(),
			"Date->Nice() works with timestamp integers"
		);
		$this->assertEquals('01/04/2008', DBField::create('Date', '1206968400')->Nice(),
			"Date->Nice() works with timestamp strings"
		);
		$this->assertEquals('31/03/2008', DBField::create('Date', '1206882000')->Nice(),
			"Date->Nice() works with timestamp strings"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '4/3/03')->Nice(),
			"Date->Nice() works with D/M/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '04/03/03')->Nice(),
			"Date->Nice() works with DD/MM/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '4/3/03')->Nice(),
			"Date->Nice() works with D/M/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '4/03/03')->Nice(),
			"Date->Nice() works with D/M/YY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '4/3/2003')->Nice(),
			"Date->Nice() works with D/M/YYYY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '4-3-2003')->Nice(),
			"Date->Nice() works with D-M-YYYY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '2003-03-04')->Nice(),
			"Date->Nice() works with YYYY-MM-DD format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '04/03/2003')->Nice(),
			"Date->Nice() works with DD/MM/YYYY format"
		);
		$this->assertEquals('04/03/2003', DBField::create('Date', '04-03-2003')->Nice(),
			"Date->Nice() works with DD/MM/YYYY format"
		);
	}
	
	function testLongDate() {
		$this->assertEquals('1 April 2008', DBField::create('Date', 1206968400)->Long(),
			"Date->Long() works with numeric timestamp"
		);
		$this->assertEquals('1 April 2008', DBField::create('Date', '1206968400')->Long(),
			"Date->Long() works with string timestamp"
		);
		$this->assertEquals('31 March 2008', DBField::create('Date', 1206882000)->Long(),
			"Date->Long() works with numeric timestamp"
		);
		$this->assertEquals('31 March 2008', DBField::create('Date', '1206882000')->Long(),
			"Date->Long() works with numeric timestamp"
		);
		$this->assertEquals('3 April 2003', DBField::create('Date', '2003-4-3')->Long(),
			"Date->Long() works with YYYY-M-D"
		);
		$this->assertEquals('3 April 2003', DBField::create('Date', '3/4/2003')->Long(),
			"Date->Long() works with D/M/YYYY"
		);
	}
	
}
?>