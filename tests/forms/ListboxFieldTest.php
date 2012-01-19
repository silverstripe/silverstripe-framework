<?php
/**
 * @package sapphire
 * @subpackage tests
 */

class ListboxFieldTest extends SapphireTest {
	
	protected $extraDataObjects = array('ListboxFieldTest_DataObject');
	
	function testSaveIntoNullValueWithMultipleOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('a');
		$field->saveInto($obj);
		$field->setValue(null);
		$field->saveInto($obj);
		$this->assertNull($obj->Choices);
	}
	
	function testSaveIntoNullValueWithMultipleOn() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('a,c');
		$field->saveInto($obj);
		$field->setValue('');
		$field->saveInto($obj);
		$this->assertEquals('', $obj->Choices);
	}
	
	function testSaveInto() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = false;
		
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('a');
		$field->saveInto($obj);
		$this->assertEquals('a', $obj->Choices);
	}
	
	function testSaveIntoMultiple() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		
		// As array
		$obj1 = new ListboxFieldTest_DataObject();
		$field->setValue(array('a', 'c'));
		$field->saveInto($obj1);
		$this->assertEquals('a,c', $obj1->Choices);
		
		// As string
		$obj2 = new ListboxFieldTest_DataObject();
		$field->setValue('a,c');
		$field->saveInto($obj2);
		$this->assertEquals('a,c', $obj2->Choices);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testSetValueFailsOnArrayIfMultipleIsOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = false;
		
		// As array (type error)
		$failsOnArray = false;
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue(array('a', 'c'));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testSetValueFailsOnStringIfChoiceInvalidAndMultipleIsOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = false;
		
		// As string (invalid choice as comma is regarded literal)
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('invalid');
	}
	
	function testFieldRenderingMultipleOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		$field->setValue('a');
		$parser = new CSSContentParser($field->Field());
		$optEls = $parser->getBySelector('option');
		$this->assertEquals(3, count($optEls));
		$this->assertEquals('selected', (string)$optEls[0]['selected']);
		$this->assertEquals('', (string)$optEls[1]['selected']);
		$this->assertEquals('', (string)$optEls[2]['selected']);
	}
	
	function testFieldRenderingMultipleOn() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		$field->setValue('a,c');
		$parser = new CSSContentParser($field->Field());
		$optEls = $parser->getBySelector('option');
		$this->assertEquals(3, count($optEls));
		$this->assertEquals('selected', (string)$optEls[0]['selected']);
		$this->assertEquals('', (string)$optEls[1]['selected']);
		$this->assertEquals('selected', (string)$optEls[2]['selected']);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testCommasInSourceKeys() {
		$choices = array('a' => 'a value', 'b,with,comma' => 'b value,with,comma',);
		$field = new ListboxField('Choices', 'Choices', $choices);
	}
	
}

class ListboxFieldTest_DataObject extends DataObject implements TestOnly {
	static $db = array(
		'Choices' => 'Text'
	);
}