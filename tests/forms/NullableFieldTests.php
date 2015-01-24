<?php
/**
 * Tests the NullableField form field class.
 * @package framework
 * @subpackage tests
 * @author Pete Bacon Darwin
 *
 */
class NullableFieldTests extends SapphireTest {

	/**
	 * Test that the NullableField works when it wraps a TextField containing actual content
	 */
	public function testWithContent() {
		$a = new NullableField(new TextField("Field1", "Field 1", "abc"));
		$this->assertEquals("Field1", $a->getName());
		$this->assertEquals("Field 1", $a->Title());
		$this->assertSame("abc", $a->Value());
		$this->assertSame("abc", $a->dataValue());
		$field = $a->Field();
		$this->assertTag(array(
			'tag'=>'input',
			'id'=>'Field1',
			'attributes'=>array('type'=>'text', 'name'=>'Field1', 'value'=>'abc'),
		), $field);
		$this->assertTag(array(
			'tag'=>'input',
			'id'=>'Field1_IsNull',
			'attributes'=>array('type'=>'checkbox', 'name'=>'Field1_IsNull', 'value'=>'1'),
		), $field);
	}
	/**
	 * Test that the NullableField works when it wraps a TextField containing an empty string
	 */
	public function testWithEmpty() {
		$a = new NullableField(new TextField("Field1", "Field 1", ""));
		$this->assertEquals("Field1", $a->getName());
		$this->assertEquals("Field 1", $a->Title());
		$this->assertSame("", $a->Value());
		$this->assertSame("", $a->dataValue());
		$field = $a->Field();
		$this->assertTag(array(
			'tag'=>'input',
			'id'=>'Field1',
			'attributes'=>array('type'=>'text', 'name'=>'Field1', 'value'=>''),
		), $field);
		$this->assertTag(array(
			'tag'=>'input',
			'id'=>'Field1_IsNull',
			'attributes'=>array('type'=>'checkbox', 'name'=>'Field1_IsNull', 'value'=>'1'),
		), $field);
	}
	/**
	 * Test that the NullableField works when it wraps a TextField containing a null string
	 */
	public function testWithNull() {
		$a = new NullableField(new TextField("Field1", "Field 1", null));
		$this->assertEquals("Field1", $a->getName());
		$this->assertEquals("Field 1", $a->Title());
		$this->assertSame(null, $a->Value());
		$this->assertSame(null, $a->dataValue());
		$field = $a->Field();
		$this->assertTag(array(
			'tag'=>'input',
			'id'=>'Field1',
			'attributes'=>array('type'=>'text', 'name'=>'Field1', 'value'=>''),
		), $field);
		$this->assertTag(array(
			'tag'=>'input',
			'id'=>'Field1_IsNull',
			'attributes'=>array('type'=>'checkbox', 'name'=>'Field1_IsNull', 'value'=>'1', 'checked'=>'checked'),
		), $field);
		unset($a);
	}

	/**
	 * Test NullableField::setValue works when passed simple values
	 */
	public function testSetValueSimple() {
		$a = new NullableField(new TextField("Field1", "Field 1"));
		$a->setValue("abc");
		$this->assertSame("abc", $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1"));
		$a->setValue(null);
		$this->assertSame(null, $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1", "abc"));
		$a->setValue(null);
		$this->assertSame(null, $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1", "abc"));
		$a->setValue("xyz");
		$this->assertSame("xyz", $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1"));
		$a->setValue("");
		$this->assertSame("", $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1", "abc"));
		$a->setValue("");
		$this->assertSame("", $a->dataValue());
	}

	/**
	 * Test NullableField::setValue works when passed an array values,
	 * which happens when the form submits.
	 */
	public function testSetValueArray() {
		$a = new NullableField(new TextField("Field1", "Field 1"));
		$a->setValue("abc", array("Field1_IsNull"=>false));
		$this->assertSame("abc", $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1"));
		$a->setValue("", array("Field1_IsNull"=>false));
		$this->assertSame("", $a->dataValue());

		$a = new NullableField(new TextField("Field1", "Field 1"));
		$a->setValue("", array("Field1_IsNull"=>true));
		$this->assertSame(null, $a->dataValue());
	}

}

