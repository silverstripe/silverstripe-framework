<?php

namespace SilverStripe\Forms;

use NumberFormatter;
use SilverStripe\i18n\i18n;

/**
 * Text input field with validation for numeric values. Supports validating
 * the numeric value as to the {@link i18n::get_locale()} value, or an
 * overridden locale specific to this field.
 */
class NumericField extends TextField
{

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DECIMAL;

    protected $inputType = 'number';

    /**
     * Used to determine if the number given is in the correct format when validating
     *
     * @var mixed
     */
    protected $originalValue = null;

    /**
     * Override locale for this field.
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Use HTML5 number input type.
     * Note that enabling html5 disables certain localisation features.
     *
     * @var bool
     */
    protected $html5 = false;

    /**
     * Number of decimal places allowed, if bound.
     * Null means unbound.
     * Defaults to 0, which is integer value.
     *
     * @var string
     */
    protected $scale = 0;

    /**
     * Get number formatter for localising this field
     *
     * @return NumberFormatter
     */
    protected function getFormatter()
    {
        if ($this->getHTML5()) {
            // Locale-independent html5 number formatter
            $formatter = NumberFormatter::create(
                i18n::config()->uninherited('default_locale'),
                NumberFormatter::DECIMAL
            );
            $formatter->setAttribute(NumberFormatter::GROUPING_USED, false);
            $formatter->setSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, '.');
        } else {
            // Locale-specific number formatter
            $formatter = NumberFormatter::create($this->getLocale(), NumberFormatter::DECIMAL);
        }

        // Set decimal precision
        $scale = $this->getScale();
        if ($scale === 0) {
            $formatter->setAttribute(NumberFormatter::DECIMAL_ALWAYS_SHOWN, false);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        } else {
            $formatter->setAttribute(NumberFormatter::DECIMAL_ALWAYS_SHOWN, true);
            if ($scale === null) {
                // At least one digit to distinguish floating point from integer
                $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 1);
            } else {
                $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $scale);
            }
        }
        return $formatter;
    }

    /**
     * Get type argument for parse / format calls. one of TYPE_INT32, TYPE_INT64 or TYPE_DOUBLE
     *
     * @return int
     */
    protected function getNumberType()
    {
        $scale = $this->getScale();
        if ($scale === 0) {
            return PHP_INT_SIZE > 4
                ? NumberFormatter::TYPE_INT64
                : NumberFormatter::TYPE_INT32;
        }
        return NumberFormatter::TYPE_DOUBLE;
    }

    /**
     * In some cases and locales, validation expects non-breaking spaces.
     * This homogenises regular, narrow and thin non-breaking spaces to a regular space character.
     *
     */
    private function clean(?string $value): string
    {
        return trim(str_replace(["\u{00A0}", "\u{202F}", "\u{2009}"], ' ', $value ?? ''));
    }

    public function setSubmittedValue($value, $data = null)
    {
        // Save original value in case parse fails
        $value = $this->clean($value);
        $this->originalValue = $value;

        // Empty string is no-number (not 0)
        if (strlen($value ?? '') === 0) {
            $this->value = null;
            return $this;
        }

        // Format number
        $formatter = $this->getFormatter();
        $parsed = 0;
        $this->value = $formatter->parse($value, $this->getNumberType(), $parsed); // Note: may store literal `false` for invalid values
        // Ensure that entire string is parsed
        if ($parsed < strlen($value ?? '')) {
            $this->value = false;
        }
        return $this;
    }

    /**
     * Format value for output
     *
     * @return string
     */
    public function Value()
    {
        // Show invalid value back to user in case of error
        if ($this->value === null || $this->value === false) {
            return $this->originalValue;
        }
        $formatter = $this->getFormatter();
        return $formatter->format($this->value, $this->getNumberType());
    }

    public function setValue($value, $data = null)
    {
        $this->originalValue = $value;
        $this->value = $this->cast($value);
        return $this;
    }

    /**
     * Helper to cast non-localised strings to their native type
     *
     * @param string $value
     * @return float|int
     */
    protected function cast($value)
    {
        if (strlen($value ?? '') === 0) {
            return null;
        }
        if ($this->getScale() === 0) {
            return (int)$value;
        }
        return (float)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        return 'numeric text';
    }

    public function getAttributes()
    {
        $attributes = parent::getAttributes();
        if ($this->getHTML5()) {
            $attributes['step'] = $this->getStep();
        } else {
            $attributes['type'] = 'text';
        }

        return $attributes;
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $result = true;
        // false signifies invalid value due to failed parse()
        if ($this->value === false) {
            $validator->validationError(
                $this->name,
                _t(
                    'SilverStripe\\Forms\\NumericField.VALIDATION',
                    "'{value}' is not a number, only numbers can be accepted for this field",
                    ['value' => $this->originalValue]
                )
            );
            $result = false;
        }

        return $this->extendValidationResult($result, $validator);
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['numeric'] = true;
        return $rules;
    }

    /**
     * Get internal database value
     *
     * @return int|float
     */
    public function dataValue()
    {
        return $this->cast($this->value);
    }

    /**
     * Gets the current locale this field is set to.
     *
     * @return string
     */
    public function getLocale()
    {
        if ($this->locale) {
            return $this->locale;
        }

        return i18n::get_locale();
    }

    /**
     * Override the locale for this field.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Determine if we should use html5 number input
     *
     * @return bool
     */
    public function getHTML5()
    {
        return $this->html5;
    }

    /**
     * Set whether this field should use html5 number input type.
     * Note: If setting to true this will disable all number localisation.
     *
     * @param bool $html5
     * @return $this
     */
    public function setHTML5($html5)
    {
        $this->html5 = $html5;
        return $this;
    }

    /**
     * Step attribute for html5. E.g. '0.01' to enable two decimal places.
     * Ignored if html5 isn't enabled.
     *
     * @return string
     */
    public function getStep()
    {
        $scale = $this->getScale();
        if ($scale === null) {
            return 'any';
        }
        if ($scale === 0) {
            return '1';
        }
        return '0.' . str_repeat('0', $scale - 1) . '1';
    }

    /**
     * Get number of digits to show to the right of the decimal point.
     * 0 for integer, any number for floating point, or null to flexible
     *
     * @return int|null
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Get number of digits to show to the right of the decimal point.
     * 0 for integer, any number for floating point, or null to flexible
     *
     * @param int|null $scale
     * @return $this
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
        return $this;
    }

    public function performReadonlyTransformation()
    {
        $field = clone $this;
        $field->setReadonly(true);
        return $field;
    }
}
