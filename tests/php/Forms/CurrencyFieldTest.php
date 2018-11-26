<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\CurrencyField_Readonly;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\FieldType\DBCurrency;

class CurrencyFieldTest extends SapphireTest
{

    public function testValidate()
    {
        $f = new CurrencyField('TestField');
        $validator = new RequiredFields();

        //tests with default currency symbol setting
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

        //tests with updated currency symbol setting
        DBCurrency::config()->update('currency_symbol', '€');

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

        $f->setValue('€123.45');
        $this->assertTrue(
            $f->validate($validator),
            'Validates positive decimals with sign'
        );

        $f->setValue('-€123.45');
        $this->assertTrue(
            $f->validate($validator),
            'Validates negative decimals with sign'
        );

        $f->setValue('€-123.45');
        $this->assertTrue(
            $f->validate($validator),
            'Validates negative decimals with sign'
        );

        $f->setValue('324511434634');
        $this->assertTrue(
            $f->validate($validator),
            'Validates large integers'
        );

        $f->setValue('test€1.23test');
        $this->assertTrue(
            $f->validate($validator),
            'Alphanumeric is valid'
        );

        $f->setValue('€test');
        $this->assertTrue(
            $f->validate($validator),
            'Words are valid'
        );
    }

    public function testSetValue()
    {
        $f = new CurrencyField('TestField');

        //tests with default currency symbol setting
        $f->setValue('123.45');
        $this->assertEquals(
            $f->Value(),
            '$123.45',
            'Prepends dollar sign to positive decimal'
        );

        $f->setValue('-123.45');
        $this->assertEquals(
            $f->Value(),
            '$-123.45',
            'Prepends dollar sign to negative decimal'
        );

        $f->setValue('$1');
        $this->assertEquals(
            $f->Value(),
            '$1.00',
            'Formats small value'
        );

        $f->setValue('$2.5');
        $this->assertEquals(
            $f->Value(),
            '$2.50',
            'Formats small value'
        );

        $f->setValue('$2500000.13');
        $this->assertEquals(
            $f->Value(),
            '$2,500,000.13',
            'Formats large value'
        );

        $f->setValue('$2.50000013');
        $this->assertEquals(
            $f->Value(),
            '$2.50',
            'Truncates long decimal portions'
        );

        $f->setValue('test123.00test');
        $this->assertEquals(
            $f->Value(),
            '$123.00',
            'Strips alpha values'
        );

        $f->setValue('test');
        $this->assertEquals(
            $f->Value(),
            '$0.00',
            'Does not set alpha values'
        );

        //update currency symbol via config
        DBCurrency::config()->update('currency_symbol', '€');

        $f->setValue('123.45');
        $this->assertEquals(
            $f->Value(),
            '€123.45',
            'Prepends dollar sign to positive decimal'
        );

        $f->setValue('-123.45');
        $this->assertEquals(
            $f->Value(),
            '€-123.45',
            'Prepends dollar sign to negative decimal'
        );

        $f->setValue('€1');
        $this->assertEquals(
            $f->Value(),
            '€1.00',
            'Formats small value'
        );

        $f->setValue('€2.5');
        $this->assertEquals(
            $f->Value(),
            '€2.50',
            'Formats small value'
        );

        $f->setValue('€2500000.13');
        $this->assertEquals(
            $f->Value(),
            '€2,500,000.13',
            'Formats large value'
        );

        $f->setValue('€2.50000013');
        $this->assertEquals(
            $f->Value(),
            '€2.50',
            'Truncates long decimal portions'
        );

        $f->setValue('test123.00test');
        $this->assertEquals(
            $f->Value(),
            '€123.00',
            'Strips alpha values'
        );

        $f->setValue('test');
        $this->assertEquals(
            $f->Value(),
            '€0.00',
            'Does not set alpha values'
        );
    }

    public function testDataValue()
    {
        $f = new CurrencyField('TestField');

        //tests with default currency symbol settings
        $f->setValue('$123.45');
        $this->assertEquals(
            $f->dataValue(),
            123.45
        );

        $f->setValue('-$123.45');
        $this->assertEquals(
            $f->dataValue(),
            -123.45
        );

        $f->setValue('$-123.45');
        $this->assertEquals(
            $f->dataValue(),
            -123.45
        );

        //tests with updated currency symbol setting
        DBCurrency::config()->update('currency_symbol', '€');

        $f->setValue('€123.45');
        $this->assertEquals(
            $f->dataValue(),
            123.45
        );

        $f->setValue('-€123.45');
        $this->assertEquals(
            $f->dataValue(),
            -123.45
        );

        $f->setValue('€-123.45');
        $this->assertEquals(
            $f->dataValue(),
            -123.45
        );
    }

    public function testDataValueReturnsEmptyFloat()
    {
        $field = new CurrencyField('Test', '', null);
        $this->assertSame(0.00, $field->dataValue());
    }

    public function testPerformReadonlyTransformation()
    {
        $field = new CurrencyField('Test');
        $result = $field->performReadonlyTransformation();
        $this->assertInstanceOf(CurrencyField_Readonly::class, $result);
    }

    public function testInvalidCurrencySymbol()
    {
        $field = new CurrencyField('Test', '', '$5.00');
        $validator = new RequiredFields();

        DBCurrency::config()->update('currency_symbol', '€');
        $result = $field->validate($validator);

        $this->assertFalse($result, 'Validation should fail since wrong currency was used');
        $this->assertFalse($validator->getResult()->isValid(), 'Validator should receive failed state');
        $this->assertContains('Please enter a valid currency', $validator->getResult()->serialize());
    }
}
