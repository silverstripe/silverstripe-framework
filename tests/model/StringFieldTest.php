<?php
/**
 * @package framework
 * @subpackage tests
 */

class StringFieldTest extends SapphireTest {

	/**
	 * @covers StringField->forTemplate()
	 */
	public function testForTemplate() {
		$this->assertEquals(
			"this is<br />\na test!",
			DBField::create_field('StringFieldTest_MyStringField', "this is\na test!")->forTemplate()
		);
	}
	
	/**
	 * @covers StringField->LowerCase()
	 */
	public function testLowerCase() {
		$this->assertEquals(
			'this is a test!',
			DBField::create_field('StringFieldTest_MyStringField', 'This is a TEST!')->LowerCase()
		);
	}

	/**
	 * @covers StringField->UpperCase()
	 */
	public function testUpperCase() {
		$this->assertEquals(
			'THIS IS A TEST!',
			DBField::create_field('StringFieldTest_MyStringField', 'This is a TEST!')->UpperCase()
		);
	}

}

class StringFieldTest_MyStringField extends StringField implements TestOnly {
	public function requireField() {}
}
