<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBCurrency;

/**
 * Readonly version of a {@link CurrencyField}.
 */
class CurrencyField_Disabled extends CurrencyField
{

    protected $disabled = true;

    /**
     * Overloaded to display the correctly formatted value for this data type
     *
     * @param array $properties
     * @return string
     */
    public function Field($properties = [])
    {
        if ($this->value) {
            $val = Convert::raw2xml($this->value);
            $val = DBCurrency::config()->get('currency_symbol')
                . number_format(preg_replace('/[^0-9.-]/', '', $val ?? '') ?? 0.0, 2);
            $valforInput = Convert::raw2att($val);
        } else {
            $valforInput = '';
        }
        return "<input class=\"text\" type=\"text\" disabled=\"disabled\""
        . " name=\"" . $this->name . "\" value=\"" . $valforInput . "\" />";
    }
}
