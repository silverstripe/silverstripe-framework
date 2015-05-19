<?php
/**
 * @package framework
 * @subpackage tests
 */
class TextFieldTest extends SapphireTest {

	/**
	 * Tests the TextField Max Length Validation Failure
	 */
	public function testMaxLengthValidationFail() {
		$textField = new TextField('TestField');
		$textField->setMaxLength(5);
		$textField->setValue("John Doe"); // 8 characters, so should fail
		$result = $textField->validate(new RequiredFields());
		$this->assertFalse($result);
	}

	/**
	 * Tests the TextField Max Length Validation Success
	 */
	public function testMaxLengthValidationSuccess() {
		$textField = new TextField('TestField');
		$textField->setMaxLength(5);
		$textField->setValue("John"); // 4 characters, so should pass
		$result = $textField->validate(new RequiredFields());
		$this->assertTrue($result);
	}
}
