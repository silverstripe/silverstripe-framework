<?php
/**
 * @package framework
 * @subpackage tests
 */

class StringFieldTest extends SapphireTest {
	
	/**
	 * @covers StringField->LowerCase()
	 */
	function testLowerCase() {
		$this->assertEquals(
			'this is a test!',
			DBField::create_field('StringFieldTest_MyStringField', 'This is a TEST!')->LowerCase()
		);
	}

	/**
	 * @covers StringField->UpperCase()
	 */
	function testUpperCase() {
		$this->assertEquals(
			'THIS IS A TEST!',
			DBField::create_field('StringFieldTest_MyStringField', 'This is a TEST!')->UpperCase()
		);
	}

}

class StringFieldTest_MyStringField extends StringField implements TestOnly {
	function requireField() {}
}
