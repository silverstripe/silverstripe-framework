<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;

/**
 * Readonly version of a {@link CurrencyField}.
 */
class CurrencyField_Disabled extends CurrencyField
{

    protected $disabled = true;

    /**
     * overloaded to display the correctly formated value for this datatype
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
            $valforInput = '';
        }
        return "<input class=\"text\" type=\"text\" disabled=\"disabled\""
        . " name=\"" . $this->name . "\" value=\"" . $valforInput . "\" />";
    }
}
