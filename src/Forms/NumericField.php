<?php

namespace SilverStripe\Forms;

use NumberFormatter;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;
use SilverStripe\i18n\i18n;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use Error;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Text input field with validation for numeric values. Supports validating
 * the numeric value as to the {@link i18n::get_locale()} value, or an
 * overridden locale specific to this field.
 */
class NumericField extends TextField
{
    private static array $field_validators = [
        NumericFieldValidator::class => [
            'minValue' => null,
            'maxValue' => null,
        ],
    ];

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

    public function __construct($name, $title = null, $value = null, $maxLength = null, $form = null)
    {
        // This constructor has a default value of null for the $value param, as opposed to the parent
        // TextField constructor which has a default value of blank string. This is done to prevent passing
        // a blank string to the NumericFieldValidator which which will cause a validation error.
        parent::__construct($name, $title, $value, $maxLength, $form);
    }

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

    public function setValue($value, $data = null)
    {
        $this->originalValue = $value;
        $this->value = $this->cast($value);
        return $this;
    }

    public function setSubmittedValue($value, $data = null)
    {
        if (is_null($value)) {
            $this->value = null;
            return $this;
        }

        // Save original value in case parse fails
        $this->originalValue = $value;

        // Empty string is no-number (not 0)
        if (mb_strlen($value ?? '') === 0) {
            $this->value = null;
            return $this;
        }

        // Format number
        $formatter = $this->getFormatter();
        $parsed = 0;
        $value = $formatter->parse($value, $this->getNumberType(), $parsed); // Note: may store literal `false` for invalid values
        // Ensure that entire string is parsed
        if ($parsed < mb_strlen($this->originalValue ?? '')) {
            $value = false;
        }
        $this->value = $this->cast($value);
        return $this;
    }

    /**
     * Format value for output
     *
     * @return string
     */
    public function getFormattedValue(): mixed
    {
        // Show invalid value back to user in case of error
        if ($this->value === null || $this->value === false) {
            return $this->originalValue;
        }
        $formatter = $this->getFormatter();
        return $formatter->format($this->value, $this->getNumberType());
    }

    public function getValueForValidation(): mixed
    {
        $value = $this->getValue();
        // If the submitted value failed to parse in the localisation formatter
        // return null so that FieldValidation is skipped
        // NumericField::validate() will "manually" add a validation message
        if ($value === false) {
            return null;
        }
        return $value;
    }

    public function validate(): ValidationResult
    {
        $this->beforeExtending('updateValidate', function (ValidationResult $result) {
            // Value will be false if the submitted value failed to parse in the localisation formatter
            if ($this->getValue() === false) {
                $result->addFieldError(
                    $this->getName(),
                    _t(
                        __CLASS__ . '.INVALID',
                        'Invalid number'
                    )
                );
            }
        });
        return parent::validate();
    }

    /**
     * Helper to cast values to numeric strings using the current scale
     */
    protected function cast(mixed $value): mixed
    {
        // If the value is false, it means the value failed to parse in the localisation formatter
        if ($value === false) {
            return false;
        }
        // If null or empty string, return null
        if (is_null($value) || is_string($value) && mb_strlen($value) === 0) {
            return null;
        }
        // If non-numeric, then return as-is. This will be caught by the validation.
        if (!is_numeric($value)) {
            return $value;
        }
        if ($this->getScale() === 0) {
            // If scale is set to 0, then remove decimal places
            // e.g. 1.00 will be cast to 1, 1.20 will be cast to 1
            $value = (int) $value;
        } else {
            // Otherwise, cast to float. This will remove any trailing deciaml zeros.
            // e.g. 1.00 will be cast to 1, 1.20 will be cast to 1.2
            $value = (float) $value;
        }
        // Return as string because numeric strings are used internally in this class
        return (string) $value;
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

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['numeric'] = true;
        return $rules;
    }

    /**
     * Get internal database value
     *
     * @return string|false
     */
    public function dataValue()
    {
        // Cast to string before passing on to model we don't know the DBField used by the model,
        // as it may be a DBString field which can't handle numeric values
        // DBInt and DBFloat can both handle numeric strings
        // This would return false if the value failed to parse in the localisation formatter
        // though the assumption is that the validate() method will have already been called
        // and the validation error message will be displayed to the user and there would
        // be no attempt to save to the database.
        $value = $this->getValue();
        return $this->cast($value);
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
