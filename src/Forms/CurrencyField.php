<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\FieldType\DBCurrency;

/**
 * Renders a text field, validating its input as a currency.
 * Limited to US-centric formats, including a hardcoded currency
 * symbol and decimal separators.
 * See {@link MoneyField} for a more flexible implementation.
 */
class CurrencyField extends TextField
{
    /**
     * allows the value to be set. removes the first character
     * if it is not a number (probably a currency symbol)
     *
     * @param mixed $value
     * @param mixed $data
     * @return $this
     */
    public function setValue($value, $data = null)
    {
        if (!$value) {
            $value = 0.00;
        }
        $this->value = DBCurrency::config()->uninherited('currency_symbol')
            . number_format((double)preg_replace('/[^0-9.\-]/', '', $value ?? ''), 2);
        return $this;
    }
    /**
     * Overwrite the datavalue before saving to the db ;-)
     * return 0.00 if no value, or value is non-numeric
     */
    public function dataValue()
    {
        if ($this->value) {
            return preg_replace('/[^0-9.\-]/', '', $this->value ?? '');
        }
        return 0.00;
    }

    public function Type()
    {
        return 'currency text';
    }

    /**
     * Create a new class for this field
     */
    public function performReadonlyTransformation()
    {
        return $this->castedCopy(CurrencyField_Readonly::class);
    }

    public function validate($validator)
    {
        $result = true;
        $currencySymbol = preg_quote(DBCurrency::config()->uninherited('currency_symbol') ?? '');
        $regex = '/^\s*(\-?' . $currencySymbol . '?|' . $currencySymbol . '\-?)?(\d{1,3}(\,\d{3})*|(\d+))(\.\d{2})?\s*$/';
        if (!empty($this->value) && !preg_match($regex ?? '', $this->value ?? '')) {
            $validator->validationError(
                $this->name,
                _t('SilverStripe\\Forms\\Form.VALIDCURRENCY', "Please enter a valid currency"),
                "validation"
            );
            $result = false;
        }

        return $this->extendValidationResult($result, $validator);
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['currency'] = true;
        return $rules;
    }
}
