<?php declare(strict_types = 1);

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

        $this->assertContains('<input', $result, 'An input should be rendered');
        $this->assertContains('readonly', $result, 'The input should be readonly');
        $this->assertContains('$5.00', $result, 'The value should be rendered');
    }

    public function testFieldWithOutValue()
    {
        DBCurrency::config()->update('currency_symbol', 'AUD');
        $field = new CurrencyField_Readonly('Test', '', null);
        $result = $field->Field();

        $this->assertContains('<input', $result, 'An input should be rendered');
        $this->assertContains('readonly', $result, 'The input should be readonly');
        $this->assertContains('AUD0.00', $result, 'The value should be rendered');
    }

    /**
     * @todo: Update the expectation when intl for currencies is implemented
     */
    public function testFieldWithCustomisedCurrencySymbol()
    {
        DBCurrency::config()->update('currency_symbol', '€');
        $field = new CurrencyField_Readonly('Test', '', '€5.00');
        $result = $field->Field();

        $this->assertContains('<input', $result, 'An input should be rendered');
        $this->assertContains('readonly', $result, 'The input should be readonly');
        $this->assertContains('€5.00', $result, 'The value should be rendered');
    }
}
