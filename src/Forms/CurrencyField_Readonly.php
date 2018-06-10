<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;

/**
 * Readonly version of a {@link CurrencyField}.
 */
class CurrencyField_Readonly extends ReadonlyField
{

    /**
     * Overloaded to display the correctly formated value for this datatype
     *
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        if ($this->value) {
            $val = Convert::raw2xml($this->value);
            $val = _t('SilverStripe\\Forms\\CurrencyField.CURRENCYSYMBOL', '$') . number_format(preg_replace('/[^0-9.-]/', "", $val), 2);
            $valforInput = Convert::raw2att($val);
        } else {
            $val = '<i>' . _t('SilverStripe\\Forms\\CurrencyField.CURRENCYSYMBOL', '$') . '0.00</i>';
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
