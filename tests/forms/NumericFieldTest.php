<?php

/**
 * @package framework
 * @subpackage tests
 */
class NumericFieldTest extends SapphireTest {
	
	protected $usesDatabase = false;

	public function testValidator() {
		i18n::set_locale('en_US');

		$field = new NumericField('Number');
		$field->setValue('12.00');

		$validator = new RequiredFields('Number');
		$this->assertTrue($field->validate($validator));

		$field->setValue('12,00');
		$this->assertFalse($field->validate($validator));

		$field->setValue('0');
		$this->assertTrue($field->validate($validator));

		$field->setValue(false);
		$this->assertFalse($field->validate($validator));

		i18n::set_locale('de_DE');
		$field->setValue('12,00');
		$validator = new RequiredFields();
		$this->assertTrue($field->validate($validator));

		$field->setValue('12.00');
		$this->assertFalse($field->validate($validator));
	}
}