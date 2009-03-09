<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DateTest extends SapphireTest {
	
	function testNiceDate() {
		/* Test the DD/MM/YYYY formatting of Date::Nice() */
		$cases = array(
			1206968400 => '01/04/2008',	// timestamp integers work as well!
			1206882000 => '31/03/2008',
			'1206968400' => '01/04/2008',	// a timestamp as a string, not an integer
			'1206882000' => '31/03/2008',
			'4/3/03' => '04/03/2003',		// D/M/YY format
			'04/03/03' => '04/03/2003',	// DD/MM/YY format
			'4/3/03' => '04/03/2003',		// D/M/YY format
			'4/03/03' => '04/03/2003',		// D/MM/YY format
			'4/3/2003' => '04/03/2003',	// D/M/YYYY format
			'4-3-2003' => '04/03/2003',	// D-M-YYYY format
			'2003-03-04' => '04/03/2003',	// YYYY-MM-DD format
			'04/03/2003' => '04/03/2003',	// DD/MM/YYYY format
			'04-03-2003' => '04/03/2003'	// DD-MM-YYYY format
		);
		
		foreach($cases as $original => $expected) {
			$date = new Date();
			$date->setValue($original);
			$this->assertEquals($expected, $date->Nice());
		}
	}
	
	function testLongDate() {
		/* "24 May 2006" style formatting of Date::Long() */
		$cases = array(
			1206968400 => '1 April 2008',
			'1206968400' => '1 April 2008',
			1206882000 => '31 March 2008',
			'1206882000' => '31 March 2008',
			'2003-4-3' => '3 April 2003',
			'3/4/2003' => '3 April 2003',
		);
		
		foreach($cases as $original => $expected) {
			$date = new Date();
			$date->setValue($original);
			$this->assertEquals($expected, $date->Long());
		}
	}
	
}
?>