<?php
/**
 * @package framework
 * @subpackage tests
 */
class TextFieldTest extends SapphireTest {

    private $fieldName = null;
    private $fieldMaxLength = null;

    public function setup()
    {
        $this->fieldName = 'TestField';
        $this->fieldMaxLength = 5;
    }

    public function testMaxLengthValidationFail()
    {
        $textField = new TextField($this->fieldName);
        $textField->setMaxLength($this->fieldMaxLength);
        $textField->setValue("John Doe");
        $validator = new TextFieldTestValidator();
        $result = $textField->validate($validator);
        $this->assertFalse($result);
    }

    public function testMaxLengthValidationSuccess()
    {
        $textField = new TextField($this->fieldName);
        $textField->setMaxLength($this->fieldMaxLength);
        $textField->setValue("John");
        $validator = new TextFieldTestValidator();
        $result = $textField->validate($validator);
        $this->assertTrue($result);
    }
}

class TextFieldTestValidator extends Validator {
    public function validationError($fieldName, $message, $messageType='') {}
    public function php($data) {}
}