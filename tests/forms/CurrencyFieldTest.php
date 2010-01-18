<?php

class CurrencyFieldTest extends SapphireTest {
	function testValidation() {
		$field = new CurrencyField('cf');
		$vr = new RequiredFields();
		
		$field->setValue('$10.23');
		$this->assertTrue($field->validate($vr));

		$field->setValue('$1a0.23');
		$this->assertFalse($field->validate($vr));
	}
	
	function testDataValues() {
		$field = new CurrencyField('cf');
		
		$field->setValue('$10.34');
		$this->assertEquals($field->dataValue(), '10.34');
		
		$field->setValue('$1s0.34');
		$this->assertEquals($field->dataValue(), '0.00');
	}
}
?>