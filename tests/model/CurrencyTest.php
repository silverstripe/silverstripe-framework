<?php
/**
 * @package framework
 * @subpackage tests
 */
class CurrencyTest extends SapphireTest {
	public function testNiceFormatting() {
		// Test a bunch of different data values and results in Nice() and Whole()
		$tests = array(
			// Test basic operation
			'$50.00' => array('$50.00', '$50'),

			// Test removal of junk text
			'this is -50.29 dollars' => array('($50.29)', '($50)'),
			'this is -50.79 dollars' => array('($50.79)', '($51)'),
			'this is 50.79 dollars' => array('$50.79', '$51'),

			// Test negative numbers
			'-1000' => array('($1,000.00)','($1,000)'),
			'-$2,000' => array('($2,000.00)', '($2,000)'),

			// Test thousands comma
			'5000' => array('$5,000.00', '$5,000'),

			// Test scientific notation
			'5.68434188608E-14' => array('$0.00', '$0'),
			'5.68434188608E7' => array('$56,843,418.86', '$56,843,419'),
			"Sometimes Es are still bad: 51 dollars, even though they\'re used in scientific notation"
				=> array('$51.00', '$51'),
			"What about 5.68434188608E7 in the middle of a string" => array('$56,843,418.86', '$56,843,419'),
		);

		foreach($tests as $value => $niceValues) {
			$c = new Currency('MyField');
			$c->setValue($value);
			$this->assertEquals($niceValues[0], $c->Nice());
			$this->assertEquals($niceValues[1], $c->Whole());
		}
	}

}
