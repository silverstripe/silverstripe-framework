<?php

/**
 * @package framework
 * @subpackage tests
 */
class CurrencyFieldTest extends SapphireTest {

	function testValidate() {
		$f = new CurrencyField('TestField');
		
		$f->setValue('123.45');
		$this->assertTrue(
			$f->validate(new RequiredFields()),
			'Validates positive decimals'
		);
		
		$f->setValue('-123.45');
		$this->assertTrue(
			$f->validate(new RequiredFields()),
			'Validates negative decimals'
		);
		
		$f->setValue('$123.45');
		$this->assertTrue(
			$f->validate(new RequiredFields()),
			'Validates positive decimals with sign'
		);
		
		$f->setValue('-$123.45');
		$this->assertTrue(
			$f->validate(new RequiredFields()),
			'Validates negative decimals with sign'
		);
		
		$f->setValue('$-123.45');
		$this->assertTrue(
			$f->validate(new RequiredFields()),
			'Validates negative decimals with sign'
		);
		
		$f->setValue('324511434634');
		$this->assertTrue(
			$f->validate(new RequiredFields()),
			'Validates large integers'
		);
	}
	
	function testSetValue() {
		$f = new CurrencyField('TestField');
		
		$f->setValue('123.45');
		$this->assertEquals(
			$f->value, '$123.45',
			'Prepends dollar sign to positive decimal'
		);
		
		$f->setValue('-123.45');
		$this->assertEquals(
			$f->value, '$-123.45',
			'Prepends dollar sign to negative decimal'
		);
		
		$f->setValue('$1');
		$this->assertEquals(
			$f->value, '$1.00',
			'Formats small value'
		);
		
		$f->setValue('$2.5');
		$this->assertEquals(
			$f->value, '$2.50',
			'Formats small value'
		);
		
		$f->setValue('$2500000.13');
		$this->assertEquals(
			$f->value, '$2,500,000.13',
			'Formats large value'
		);
		
		$f->setValue('$2.50000013');
		$this->assertEquals(
			$f->value, '$2.50',
			'Truncates long decimal portions'
		);
	}
	
	function testDataValue() {
		$f = new CurrencyField('TestField');
		
		$f->setValue('$123.45');
		$this->assertEquals(
			$f->dataValue(), 123.45
		);
		
		$f->setValue('-$123.45');
		$this->assertEquals(
			$f->dataValue(), -123.45
		);
		
		$f->setValue('$-123.45');
		$this->assertEquals(
			$f->dataValue(), -123.45
		);
	}
}
