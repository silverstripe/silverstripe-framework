<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ConfirmedPasswordFieldTest extends SapphireTest {
	function testSetValue() {
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA');
		$this->assertEquals('valueA', $field->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->Name() . '[_Password]')->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->Name() . '[_ConfirmPassword]')->Value());
		$field->setValue('valueB');
		$this->assertEquals('valueB', $field->Value());
		$this->assertEquals('valueB', $field->children->fieldByName($field->Name() . '[_Password]')->Value());
		$this->assertEquals('valueB', $field->children->fieldByName($field->Name() . '[_ConfirmPassword]')->Value());
	}
}
