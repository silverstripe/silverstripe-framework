<?php
/**
 * @package framework
 * @subpackage tests
 */
class ConfirmedPasswordFieldTest extends SapphireTest {

	public function testSetValue() {
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA');
		$this->assertEquals('valueA', $field->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
		$field->setValue('valueB');
		$this->assertEquals('valueB', $field->Value());
		$this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
		$this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
	}

	public function testHashHidden() {
		$field = new ConfirmedPasswordField('Password', 'Password', 'valueA');
		$field->setCanBeEmpty(true);

		$this->assertEquals('valueA', $field->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());

		$member = new Member();
		$member->Password = "valueB";
		$member->write();

		$form = new Form($this, 'Form', new FieldList($field), new FieldList());
		$form->loadDataFrom($member);

		$this->assertEquals('', $field->Value());
		$this->assertEquals('', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
		$this->assertEquals('', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
	}

	public function testSetShowOnClick() {
		//hide by default and display show/hide toggle button
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, true);
		$fieldHTML = $field->Field();
		$this->assertContains("showOnClickContainer", $fieldHTML,
			"Test class for hiding/showing the form contents is set");
		$this->assertContains("showOnClick", $fieldHTML,
			"Test class for hiding/showing the form contents is set");

		//show all by default
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, false);
		$fieldHTML = $field->Field();
		$this->assertNotContains("showOnClickContainer", $fieldHTML,
			"Test class for hiding/showing the form contents is set");
		$this->assertNotContains("showOnClick", $fieldHTML,
			"Test class for hiding/showing the form contents is set");
	}

	public function testValidation() {
		$field = new ConfirmedPasswordField('Test', 'Testing', array(
			"_Password" => "abc123",
			"_ConfirmPassword" => "abc123"
		));
		$validator = new RequiredFields();
		$form = new Form($this, 'Form', new FieldList($field), new FieldList(), $validator);
		$this->assertTrue(
			$field->validate($validator),
			"Validates when both passwords are the same"
		);
		$field->setName("TestNew"); //try changing name of field
		$this->assertTrue(
			$field->validate($validator),
			"Validates when field name is changed"
		);
		//non-matching password should make the field invalid
		$field->setValue(array(
			"_Password" => "abc123",
			"_ConfirmPassword" => "123abc"
		));
		$this->assertFalse(
			$field->validate($validator),
			"Does not validate when passwords differ"
		);
	}

}
