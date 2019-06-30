<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CurrencyField_Disabled;
use SilverStripe\ORM\FieldType\DBCurrency;

class CurrencyFieldDisabledTest extends SapphireTest
{
    public function testFieldWithValue()
    {
        $field = new CurrencyField_Disabled('Test', '', '$5.00');
        $result = $field->Field();

        $this->assertContains('<input', $result, 'An input should be rendered');
        $this->assertContains('disabled', $result, 'The input should be disabled');
        $this->assertContains('$5.00', $result, 'The value should be rendered');
    }

    /**
     * @todo: Update the expectation when intl for currencies is implemented
     */
    public function testFieldWithCustomisedCurrencySymbol()
    {
        DBCurrency::config()->update('currency_symbol', '€');
        $field = new CurrencyField_Disabled('Test', '', '€5.00');
        $result = $field->Field();

        $this->assertContains('<input', $result, 'An input should be rendered');
        $this->assertContains('disabled', $result, 'The input should be disabled');
        $this->assertContains('€5.00', $result, 'The value should be rendered');
    }
}
