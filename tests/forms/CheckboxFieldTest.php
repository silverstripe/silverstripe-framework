<?php
/**
 * @package framework
 * @subpackage tests
 */
class CheckboxFieldTest extends SapphireTest {
	
	protected $usesDatabase = true;

	protected $extraDataObjects = array(
		'CheckboxFieldTest_Article',
	);

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
		
		/* dataValue() for the field is null */
		$this->assertEquals($field->dataValue(), null, 'dataValue() returns a 0');
		
		/* Value() returns 0 as well */
		$this->assertEquals($field->Value(), 0, 'Value() returns a 0');
	}
	
	function testFieldValueSettingFalse() {
		/* Create the field, and set the value as NULL */
		$field = new CheckboxField('IsChecked', 'Checked');
		$field->setValue(false);
		
		/* dataValue() for the field is null */
		$this->assertEquals($field->dataValue(), null, 'dataValue() returns a 0');
		
		/* Value() returns 0 as well */
		$this->assertEquals($field->Value(), 0, 'Value() returns a 0');
	}
	
	function testFieldValueWithoutSettingValue() {
		/* Create the field, but don't set any value on it */
		$field = new CheckboxField('IsChecked', 'Checked');
		
		/* dataValue() for the field is null */
		$this->assertEquals($field->dataValue(), null, 'dataValue() returns a 0');
		
		/* Value() returns 0 as well */
		$this->assertEquals($field->Value(), 0, 'Value() returns a 0');
	}
	
	function testSavingChecked() {
		/* Create a new test data record */
		$article = new CheckboxFieldTest_Article();
		
		/* Create a field, with a value of 1 */
		$field = new CheckboxField('IsChecked', 'Checked', 1);
		
		/* Save the field into our Article object */
		$field->saveInto($article);
		
		/* Write the record to the test database */
		$article->write();
		
		/* Check that IsChecked column contains a 1 */
		$this->assertEquals(
			DB::query("SELECT \"IsChecked\" FROM \"CheckboxFieldTest_Article\"")->value(),
			1,
			'We have a 1 set in the database, because the field saved into as a 1'
		);
		
		/* Delete the record we tested */
		$article->delete();
	}
	
	function testSavingUnchecked() {
		/* Create a new test data record */
		$article = new CheckboxFieldTest_Article();
		
		/* Create a field, with no value */
		$field = new CheckboxField('IsChecked', 'Checked');
		
		/* Save the field into our Article object */
		$field->saveInto($article);
		
		/* Write the record to the test database */
		$article->write();
		
		/* Check that IsChecked column contains a 0 */
		$this->assertEquals(
			DB::query("SELECT \"IsChecked\" FROM \"CheckboxFieldTest_Article\"")->value(),
			0,
			'We have a 0 set in the database, because the field saved into as a 0'
		);
		
		/* Delete the record we tested */
		$article->delete();
	}

	function testReadonlyCheckboxField() {
		// Test 1: a checked checkbox goes to "Yes"
		$field1 = new CheckboxField('IsChecked', 'Checked');
		$field1->setValue('on');
		$this->assertEquals(_t('CheckboxField.YES', 'Yes'), trim(strip_tags($field1->performReadonlyTransformation()->Field())));

		// Test 2: an checkbox with the value set to false to "No"
		$field2 = new CheckboxField('IsChecked', 'Checked');
		$field2->setValue(false);
		$this->assertEquals(_t('CheckboxField.NO', 'No'), trim(strip_tags($field2->performReadonlyTransformation()->Field())));

		// Test 3: an checkbox with no value ever set goes to "No"
		$field3 = new CheckboxField('IsChecked', 'Checked');
		$this->assertEquals(_t('CheckboxField.NO', 'No'), trim(strip_tags($field3->performReadonlyTransformation()->Field())));

	}
	
}
class CheckboxFieldTest_Article extends DataObject implements TestOnly {
	
	public static $db = array(
		'IsChecked' => 'Boolean'
	);
	
}
