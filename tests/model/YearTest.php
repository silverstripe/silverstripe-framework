<?php
/**
 * @package framework
 * @subpackage tests
 */
class YearTest extends SapphireTest {

	/**
	 * Test that the scaffolding form field works
	 */
	public function testScaffoldFormFieldFirst() {
		$year = new Year();
		$field = $year->scaffoldFormField("YearTest");
		$this->assertEquals("DropdownField", get_class($field));

		//This should be a list of years from the current one, counting down to 1900
		$source = $field->getSource();

		$lastValue = end($source);
		$lastKey = key($source);

		//Keys and values should be the same - and the last one should be 1900
		$this->assertEquals(1900, $lastValue);
		$this->assertEquals(1900, $lastKey);
	}

	public function testScaffoldFormFieldLast() {
		$year = new Year();
		$field = $year->scaffoldFormField("YearTest");
		$source = $field->getSource();

		//The first one should be the current year
		$currentYear = (int)date('Y');
		$firstValue = reset($source);
		$firstKey = key($source);

		$this->assertEquals($currentYear, $firstValue);
		$this->assertEquals($currentYear, $firstKey);

	}
}
