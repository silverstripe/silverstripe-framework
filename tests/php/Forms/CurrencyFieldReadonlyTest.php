<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CurrencyField_Readonly;
use SilverStripe\ORM\FieldType\DBCurrency;

class CurrencyFieldReadonlyTest extends SapphireTest
{
    public function testPerformReadonlyTransformation()
    {
        $field = new CurrencyField_Readonly('Test', '', '$5.00');
        $result = $field->performReadonlyTransformation();
        $this->assertInstanceOf(CurrencyField_Readonly::class, $result);
        $this->assertNotSame($result, $field, 'Should return a clone of the field');
    }

    public function testFieldWithValue()
    {
        $field = new CurrencyField_Readonly('Test', '', '$5.00');
        $result = $field->Field();

        $this->assertStringContainsString('<input', $result, 'An input should be rendered');
        $this->assertStringContainsString('readonly', $result, 'The input should be readonly');
        $this->assertStringContainsString('$5.00', $result, 'The value should be rendered');
    }

    public function testFieldWithOutValue()
    {
        DBCurrency::config()->set('currency_symbol', 'AUD');
        $field = new CurrencyField_Readonly('Test', '', null);
        $result = $field->Field();

        $this->assertStringContainsString('<input', $result, 'An input should be rendered');
        $this->assertStringContainsString('readonly', $result, 'The input should be readonly');
        $this->assertStringContainsString('AUD0.00', $result, 'The value should be rendered');
    }

    public function testFieldWithCustomisedCurrencySymbol()
    {
        DBCurrency::config()->set('currency_symbol', '€');
        $field = new CurrencyField_Readonly('Test', '', '€5.00');
        $result = $field->Field();

        $this->assertStringContainsString('<input', $result, 'An input should be rendered');
        $this->assertStringContainsString('readonly', $result, 'The input should be readonly');
        $this->assertStringContainsString('€5.00', $result, 'The value should be rendered');
    }
}
