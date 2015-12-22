<?php
/**
 * @package framework
 * @subpackage tests
 */
class OptionsetFieldTest extends SapphireTest {
	public function testSetDisabledItems() {
		$f = new OptionsetField(
			'Test',
			false,
			array(0 => 'Zero', 1 => 'One')
		);

		$f->setDisabledItems(array(0));
		$p = new CSSContentParser($f->Field());
		$item0 = $p->getBySelector('#Test_0');
		$item1 = $p->getBySelector('#Test_1');
		$this->assertEquals(
			(string)$item0[0]['disabled'],
			'disabled'
		);
		$this->assertEquals(
			(string)$item1[0]['disabled'],
			''
		);
	}

	public function testValidation() {
		$field = OptionsetField::create('Test', 'Testing', array(
			"One" => "One",
			"Two" => "Two",
			"Five" => "Five"
		));
		$validator = new RequiredFields('Test');
		$form = new Form($this, 'Form', new FieldList($field), new FieldList(), $validator);

		$field->setValue("One");
		$this->assertTrue($field->validate($validator));

		//non-existent value should make the field invalid
		$field->setValue("Three");
		$this->assertFalse($field->validate($validator));

		//empty string should pass field-level validation...
		$field->setValue('');
		$this->assertTrue($field->validate($validator));

		// ... but should not pass "RequiredFields" validation
		$this->assertFalse($form->validate());

		//disabled items shouldn't validate
		$field->setDisabledItems(array('Five'));
		$field->setValue('Five');
		$this->assertFalse($field->validate($validator));
	}

	public function testReadonlyField() {
		$sourceArray = array(0 => 'No', 1 => 'Yes');
		$field = new OptionsetField('FeelingOk', 'are you feeling ok?', $sourceArray, 1);
		$field->setEmptyString('(Select one)');
		$field->setValue(1);
		$readonlyField = $field->performReadonlyTransformation();
		preg_match('/Yes/', $field->Field(), $matches);
		$this->assertEquals($matches[0], 'Yes');
	}
}
