<?php

/**
 * @package framework
 * @subpackage tests
 */
class CurrencyFieldTest extends SapphireTest {

	public function testValidate() {
		$f = new CurrencyField('TestField');
		$validator = new RequiredFields();

		$f->setValue('123.45');
		$this->assertTrue(
			$f->validate($validator),
			'Validates positive decimals'
		);

		$f->setValue('-123.45');
		$this->assertTrue(
			$f->validate($validator),
			'Validates negative decimals'
		);

		$f->setValue('$123.45');
		$this->assertTrue(
			$f->validate($validator),
			'Validates positive decimals with sign'
		);

		$f->setValue('-$123.45');
		$this->assertTrue(
			$f->validate($validator),
			'Validates negative decimals with sign'
		);

		$f->setValue('$-123.45');
		$this->assertTrue(
			$f->validate($validator),
			'Validates negative decimals with sign'
		);

		$f->setValue('324511434634');
		$this->assertTrue(
			$f->validate($validator),
			'Validates large integers'
		);

		$f->setValue('test$1.23test');
		$this->assertTrue(
			$f->validate($validator),
			'Alphanumeric is valid'
		);

		$f->setValue('$test');
		$this->assertTrue(
			$f->validate($validator),
			'Words are valid'
		);
	}

	public function testSetValue() {
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

		$f->setValue('test123.00test');
		$this->assertEquals(
			$f->value, '$123.00',
			'Strips alpha values'
		);

		$f->setValue('test');
		$this->assertEquals(
			$f->value, '$0.00',
			'Does not set alpha values'
		);
	}

	public function testDataValue() {
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
