<?php

/**
 * @package framework
 * @subpackage tests
 */
class GroupedDropdownFieldTest extends SapphireTest {

	public function testValidation() {
		$field = GroupedDropdownField::create('Test', 'Testing', [
			"1" => "One",
			"Group One" => [
				"2" => "Two",
				"3" => "Three"
			],
			"Group Two" => [
				"4" => "Four"
			]
		]);

		$validator = new RequiredFields();

		$field->setValue("1");
		$this->assertTrue($field->validate($validator));

		//test grouped values
		$field->setValue("3");
		$this->assertTrue($field->validate($validator));

		//non-existent value should make the field invalid
		$field->setValue("Over 9000");
		$this->assertFalse($field->validate($validator));

		//empty string shouldn't validate
		$field->setValue('');
		$this->assertFalse($field->validate($validator));

		//empty field should validate after being set
		$field->setEmptyString('Empty String');
		$field->setValue('');
		$this->assertTrue($field->validate($validator));

		//disabled items shouldn't validate
		$field->setDisabledItems(['1']);
		$field->setValue('1');
		$this->assertFalse($field->validate($validator));

		//grouped disabled items shouldn't validate
		$field->setDisabledItems(["Group One" => ["2"]]);
		$field->setValue('2');
		$this->assertFalse($field->validate($validator));
	}

}
