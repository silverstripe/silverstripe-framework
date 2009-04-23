<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class CheckboxFieldTest extends SapphireTest {

	function testFieldValueTrue() {
		/* Create the field, and set the value as boolean true */
		$field = new CheckboxField('IsChecked', 'Checked');
		$field->setValue(true);
		
		/* dataValue() for the field is 1 */
		$this->assertEquals($field->dataValue(), 1, 'dataValue() returns a 1');
		
		/* Value() returns 1 as well */
		$this->assertEquals($field->Value(), 1, 'Value() returns a 1');
	}
	
	function testFieldValueString() {
		/* Create the field, and set the value as "on" (raw request field value from DOM) */
		$field = new CheckboxField('IsChecked', 'Checked');
		$field->setValue('on');
		
		/* dataValue() for the field is 1 */
		$this->assertEquals($field->dataValue(), 1, 'dataValue() returns a 1');
		
		/* Value() returns 1 as well */
		$this->assertEquals($field->Value(), 1, 'Value() returns a 1');
	}
	
	function testFieldValueSettingNull() {
		/* Create the field, and set the value as NULL */
		$field = new CheckboxField('IsChecked', 'Checked');
		$field->setValue(null);
		
		/* dataValue() for the field is 0 */
		$this->assertEquals($field->dataValue(), 0, 'dataValue() returns a 0');
		
		/* Value() returns 0 as well */
		$this->assertEquals($field->Value(), 0, 'Value() returns a 0');
	}
	
	function testFieldValueSettingFalse() {
		/* Create the field, and set the value as NULL */
		$field = new CheckboxField('IsChecked', 'Checked');
		$field->setValue(false);
		
		/* dataValue() for the field is 0 */
		$this->assertEquals($field->dataValue(), 0, 'dataValue() returns a 0');
		
		/* Value() returns 0 as well */
		$this->assertEquals($field->Value(), 0, 'Value() returns a 0');
	}
	
	function testFieldValueWithoutSettingValue() {
		/* Create the field, but don't set any value on it */
		$field = new CheckboxField('IsChecked', 'Checked');
		
		/* dataValue() for the field is 0 */
		$this->assertEquals($field->dataValue(), 0, 'dataValue() returns a 0');
		
		/* Value() returns 0 as well */
		$this->assertEquals($field->Value(), 0, 'Value() returns a 0');
	}
	
}
?>