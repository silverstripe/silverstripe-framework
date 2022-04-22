<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBCurrency;

/**
 * Readonly version of a {@link CurrencyField}.
 */
class CurrencyField_Readonly extends ReadonlyField
{

    /**
     * Overloaded to display the correctly formatted value for this data type
     *
     * @param array $properties
     * @return string
     */
    public function Field($properties = [])
    {
        $currencySymbol = DBCurrency::config()->get('currency_symbol');
        if ($this->value) {
            $val = Convert::raw2xml($this->value);
            $val = $currencySymbol . number_format(preg_replace('/[^0-9.-]/', '', $val ?? '') ?? 0.0, 2);
            $valforInput = Convert::raw2att($val);
        } else {
            $val = '<i>' . $currencySymbol . '0.00</i>';
            $valforInput = '';
        }
        return "<span class=\"readonly " . $this->extraClass() . "\" id=\"" . $this->ID() . "\">$val</span>"
        . "<input type=\"hidden\" name=\"" . $this->name . "\" value=\"" . $valforInput . "\" />";
    }

    /**
     * This already is a readonly field.
     */
    public function performReadonlyTransformation()
    {
        return clone $this;
    }
}
