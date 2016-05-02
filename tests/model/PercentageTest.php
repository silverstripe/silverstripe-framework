<?php
/**
 * @package framework
 * @subpackage tests
 */
class PercentageTest extends SapphireTest {

	public function testNice() {
		/* Test the default Nice() output of Percentage */
		$cases = array(
			'0.01' => '1.00%',
			'0.10' => '10.00%',
			'1' => '100.00%',
			'1.5' => '150.00%',
			'1.5000' => '150.00%',
			'1.05' => '105.00%',
			'1.0500' => '105.00%',
			'0.95' => '95.00%'
		);

		foreach($cases as $original => $expected) {
			$percentage = new Percentage('Probability');
			$percentage->setValue($original);
			$this->assertEquals($expected, $percentage->Nice());
		}
	}

	public function testCustomPrecision() {
		/* Set a precision that's different from the default with Nice() output */
		$cases = array(
			'0.01' => '1%',
			'0.1' => '10%',
			'1' => '100%',
			'1.5' => '150%',
			'1.05' => '105%',
			'1.0500' => '105%'
		);

		foreach($cases as $original => $expected) {
			$percentage = new Percentage('Probability', 2);
			$percentage->setValue($original);
			$this->assertEquals($expected, $percentage->Nice());
		}
	}

}
