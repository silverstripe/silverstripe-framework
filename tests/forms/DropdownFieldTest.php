<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DropdownFieldTest extends SapphireTest {
	
	function testAddExtraClass() {
		/* DropdownField has an extra class name and is in the HTML the field returns */
		$dropdownField = new DropdownField('FeelingOk', 'Are you feeling ok?', array(0 => 'No', 1 => 'Yes'), '', null, '(Select one)');
		$dropdownField->addExtraClass('thisIsMyExtraClassForDropdownField');
		preg_match('/thisIsMyExtraClassForDropdownField/', $dropdownField->Field(), $matches);
		$this->assertTrue($matches[0] == 'thisIsMyExtraClassForDropdownField');
	}
	
	function testGetSource() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source);
		$this->assertEquals(
			$field->getSource(),
			array(
				1 => 'one'
			)
		);
	}
	
	function testEmptyStringAsBooleanConstructorArgument() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source, null, null, true);
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => '',
				1 => 'one'
			)
		);
	}
	
	function testEmptyStringAsLiteralConstructorArgument() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source, null, null, 'select...');
		$this->assertEquals(
			$field->getSource(),
			array(
				"" => 'select...',
				1 => 'one'
			)
		);
	}
	
	function testHasEmptyDefault() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source);
		$field->setHasEmptyDefault(true);
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => '',
				1 => 'one'
			)
		);
	}
	
	function testEmptyDefaultStringThroughSetter() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source);
		$field->setEmptyString('select...');
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => 'select...',
				1 => 'one'
			)
		);
		$this->assertTrue(
			$field->getHasEmptyDefault()
		);
	}
	
	function testZeroArraySourceNotOverwrittenByEmptyString() {
		$source = array(0=>'zero');
		$field = new DropdownField('Field', null, $source);
		$field->setEmptyString('select...');
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => 'select...',
				0 => 'zero'
			)
		);
	}
}
?>