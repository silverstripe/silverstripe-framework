<?php
/**
 * @package framework
 * @subpackage tests
 */
class EnumFieldTest extends SapphireTest {
	public function testAnyFieldIsPresentInSearchField() {
		$values = array (
				'Key' => 'Value'
		);
		$enumField = new Enum('testField', $values);

		$searchField = $enumField->scaffoldSearchField();

		$anyText = "(" . _t('Enum.ANY', 'Any') . ")";
		$this->assertEquals(true, $searchField->getHasEmptyDefault());
		$this->assertEquals($anyText, $searchField->getEmptyString());
	}
}
