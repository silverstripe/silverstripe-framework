<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\ORM\DataObjectInterface;

/**
 * A form field that can save into a {@link Money} database field.
 * See {@link CurrencyField} for a similar implementation
 * that can save into a single float database field without indicating the currency.
 *
 * @author Ingo Schommer, SilverStripe Ltd. (<firstname>@silverstripe.com)
 */
class MoneyField extends FormField
{

    protected $schemaDataType = 'MoneyField';

    /**
     * Limit the currencies
     *
     * @var array
     */
    protected $allowedCurrencies = [];

    /**
     * @var NumericField
     */
    protected $fieldAmount = null;

    /**
     * @var FormField
     */
    protected $fieldCurrency = null;

    /**
     * Gets field for the currency selector
     *
     * @return FormField
     */
    public function getCurrencyField()
    {
        return $this->fieldCurrency;
    }

    /**
     * Gets field for the amount input
     *
     * @return NumericField
     */
    public function getAmountField()
    {
        return $this->fieldAmount;
    }

    public function __construct($name, $title = null, $value = "")
    {
        $this->setName($name);
        $this->buildAmountField();
        $this->buildCurrencyField();

        parent::__construct($name, $title, $value);
    }

    public function __clone()
    {
        $this->fieldAmount = clone $this->fieldAmount;
        $this->fieldCurrency = clone $this->fieldCurrency;
    }

    /**
     * Builds a field to input the amount of money
     */
    protected function buildAmountField(): void
    {
        $this->fieldAmount = NumericField::create(
            $this->name . '[Amount]',
            _t('SilverStripe\\Forms\\MoneyField.FIELDLABELAMOUNT', 'Amount')
        )
            ->setScale(2);
    }

    /**
     * Builds a new currency field based on the allowed currencies configured
     *
     * @return FormField
     */
    protected function buildCurrencyField()
    {
        $name = $this->getName();

        // Validate allowed currencies
        $currencyValue = $this->fieldCurrency ? $this->fieldCurrency->dataValue() : null;
        $allowedCurrencies = $this->getAllowedCurrencies();
        if (count($allowedCurrencies ?? []) === 1) {
            // Hidden field for single currency
            $field = HiddenField::create("{$name}[Currency]");
            reset($allowedCurrencies);
            $currencyValue = key($allowedCurrencies ?? []);
        } elseif ($allowedCurrencies) {
            // Dropdown field for multiple currencies
            $field = DropdownField::create(
                "{$name}[Currency]",
                _t('SilverStripe\\Forms\\MoneyField.FIELDLABELCURRENCY', 'Currency'),
                $allowedCurrencies
            );
        } else {
            // Free-text entry for currency value
            $field = TextField::create(
                "{$name}[Currency]",
                _t('SilverStripe\\Forms\\MoneyField.FIELDLABELCURRENCY', 'Currency')
            );
        }

        $field->setReadonly($this->isReadonly());
        $field->setDisabled($this->isDisabled());
        if ($currencyValue) {
            $field->setValue($currencyValue);
        }
        $this->fieldCurrency = $field;
        return $field;
    }

    public function setSubmittedValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldCurrency->setValue(null);
            $this->fieldAmount->setValue(null);
            return $this;
        }

        // Handle submitted array value
        if (!is_array($value)) {
            throw new InvalidArgumentException("Value is not submitted array");
        }

        // Update each field
        $this->fieldCurrency->setSubmittedValue($value['Currency'], $value);
        $this->fieldAmount->setSubmittedValue($value['Amount'], $value);

        // Get data value
        $this->value = $this->dataValue();
        return $this;
    }

    public function setValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldCurrency->setValue(null);
            $this->fieldAmount->setValue(null);
            return $this;
        }

        // Convert string to array
        // E.g. `44.00 NZD`
        if (is_string($value) &&
            preg_match('/^(?<amount>[\\d\\.]+)( (?<currency>\w{3}))?$/i', $value ?? '', $matches)
        ) {
            $currency = isset($matches['currency']) ? strtoupper($matches['currency']) : null;
            $value = [
                'Currency' => $currency,
                'Amount' => (float)$matches['amount'],
            ];
        } elseif ($value instanceof DBMoney) {
            $value = [
                'Currency' => $value->getCurrency(),
                'Amount' => $value->getAmount(),
            ];
        } elseif (!is_array($value)) {
            throw new InvalidArgumentException("Invalid currency format");
        }

        // Save value
        $this->fieldCurrency->setValue($value['Currency'], $value);
        $this->fieldAmount->setValue($value['Amount'], $value);
        $this->value = $this->dataValue();
        return $this;
    }

    /**
     * Get value as DBMoney object useful for formatting the number
     *
     * @return DBMoney
     */
    protected function getDBMoney()
    {
        return DBMoney::create_field('Money', [
            'Currency' => $this->fieldCurrency->dataValue(),
            'Amount' => $this->fieldAmount->dataValue()
        ])
            ->setLocale($this->getLocale());
    }

    public function dataValue()
    {
        // Non-localised money
        return $this->getDBMoney()->getValue();
    }

    public function Value()
    {
        // Localised money
        return $this->getDBMoney()->Nice();
    }

    /**
     * 30/06/2009 - Enhancement:
     * SaveInto checks if set-methods are available and use them
     * instead of setting the values in the money class directly. saveInto
     * initiates a new Money class object to pass through the values to the setter
     * method.
     *
     * (see @link MoneyFieldTest_CustomSetter_Object for more information)
     *
     * @param DataObjectInterface|Object $dataObject
     */
    public function saveInto(DataObjectInterface $dataObject)
    {
        $fieldName = $this->getName();
        if ($dataObject->hasMethod("set$fieldName")) {
            $dataObject->$fieldName = $this->getDBMoney();
        } else {
            $currencyField = "{$fieldName}Currency";
            $amountField = "{$fieldName}Amount";

            $dataObject->$currencyField = $this->fieldCurrency->dataValue();
            $dataObject->$amountField = $this->fieldAmount->dataValue();
        }
    }

    /**
     * Returns a readonly version of this field.
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);
        return $clone;
    }

    public function setReadonly($bool)
    {
        parent::setReadonly($bool);

        $this->fieldAmount->setReadonly($bool);
        $this->fieldCurrency->setReadonly($bool);

        return $this;
    }

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);

        $this->fieldAmount->setDisabled($bool);
        $this->fieldCurrency->setDisabled($bool);

        return $this;
    }

    /**
     * Set list of currencies. Currencies should be in the 3-letter ISO 4217 currency code.
     *
     * @param array $currencies
     * @return $this
     */
    public function setAllowedCurrencies($currencies)
    {
        if (empty($currencies)) {
            $currencies = [];
        } elseif (is_string($currencies)) {
            $currencies = [
                $currencies => $currencies
            ];
        } elseif (!is_array($currencies)) {
            throw new InvalidArgumentException("Invalid currency list");
        } elseif (!ArrayLib::is_associative($currencies)) {
            $currencies = array_combine($currencies ?? [], $currencies ?? []);
        }

        $this->allowedCurrencies = $currencies;

        // Rebuild currency field
        $this->buildCurrencyField();
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return $this->allowedCurrencies;
    }

    /**
     * Assign locale to format this currency in
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->fieldAmount->setLocale($locale);
        return $this;
    }

    /**
     * Get locale to format this currency in.
     * Defaults to current locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->fieldAmount->getLocale();
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Validate currency
        $currencies = $this->getAllowedCurrencies();
        $currency = $this->fieldCurrency->dataValue();
        if ($currency && $currencies && !in_array($currency, $currencies ?? [])) {
            $validator->validationError(
                $this->getName(),
                _t(
                    __CLASS__ . '.INVALID_CURRENCY',
                    'Currency {currency} is not in the list of allowed currencies',
                    ['currency' => $currency]
                )
            );
            return $this->extendValidationResult(false, $validator);
        }

        // Field-specific validation
        $result = $this->fieldAmount->validate($validator) && $this->fieldCurrency->validate($validator);
        return $this->extendValidationResult($result, $validator);
    }

    public function setForm($form)
    {
        $this->fieldCurrency->setForm($form);
        $this->fieldAmount->setForm($form);
        return parent::setForm($form);
    }
}
