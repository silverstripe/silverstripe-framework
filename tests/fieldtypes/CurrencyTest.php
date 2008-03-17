<?php

class CurrencyTest extends SapphireTest {
	function testNiceFormatting() {
		// Test a bunch of different data values and results in Nice() and Whole()
		$tests = array(
			'$50.00' => array('$50.00', '$50'),
			'this is -50.29 dollars' => array('($50.29)', '($50)'),
			'this is -50.79 dollars' => array('($50.79)', '($51)'),
			'this is 50.79 dollars' => array('$50.79', '$51'),
			'-1000' => array('($1,000.00)','($1,000)'),
			'-$2000' => array('($2,000.00)', '($2,000)'),
			'5000' => array('$5,000.00', '$5,000'),
		);
		
		foreach($tests as $value => $niceValues) {
			$c = new Currency('MyField');
			$c->setValue($value);
			$this->assertEquals($niceValues[0], $c->Nice());
			$this->assertEquals($niceValues[1], $c->Whole());
		}
	}	
	
}