<?php
/**
 * @package framework
 * @subpackage tests
 */
class ConfirmedPasswordFieldTest extends SapphireTest {
	function testSetValue() {
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA');
		$this->assertEquals('valueA', $field->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
		$this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
		$field->setValue('valueB');
		$this->assertEquals('valueB', $field->Value());
		$this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
		$this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
	}

	function testSetShowOnClick() {
		//hide by default and display show/hide toggle button
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, true);
		$fieldHTML = $field->Field();
		$this->assertContains("showOnClickContainer",$fieldHTML,"Test class for hiding/showing the form contents is set");
		$this->assertContains("showOnClick",$fieldHTML,"Test class for hiding/showing the form contents is set");

		//show all by default
		$field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, false);
		$fieldHTML = $field->Field();
		$this->assertNotContains("showOnClickContainer",$fieldHTML,"Test class for hiding/showing the form contents is set");
		$this->assertNotContains("showOnClick",$fieldHTML,"Test class for hiding/showing the form contents is set");
	}
}
