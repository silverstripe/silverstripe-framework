<?php

/**
 * @package framework
 * @subpackage tests
 */

class StringFieldTest extends SapphireTest {
	
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

	/**
	 * @covers StringField->Nl2Br()
	 */
	public function testNl2Br() {
		$this->assertEquals(
			"String<br />line",
			DBField::create_field('StringFieldTest_MyStringField', 'String\nLine')->Nl2Br()
		);
	}

	public function testChainingCommands() {
		$this->assertEquals(
			'string<br />line"',
			DBField::create_field('StringFieldTest_MyStringField', 'String\nLine')->Nl2Br()->Lower()
		);
	}

}

class StringFieldTest_MyStringField extends StringField implements TestOnly {
	public function requireField() {}
}
