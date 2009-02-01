<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DateTest extends SapphireTest {
	
	function testNiceDate() {
		/* Test the DD/MM/YYYY formatting of Date::Nice() */
		$cases = array(
			'4/3/03' => '04/03/2003',
			'04/03/03' => '04/03/2003',
			'4/3/03' => '04/03/2003',
			'4/03/03' => '04/03/2003',
			'4/3/2003' => '04/03/2003',
			'4-3-2003' => '04/03/2003',
			'2003-03-04' => '04/03/2003',
			'04/03/2003' => '04/03/2003',
			'04-03-2003' => '04/03/2003'
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