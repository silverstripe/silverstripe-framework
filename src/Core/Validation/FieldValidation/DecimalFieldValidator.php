<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;

/**
 * Validates that a value is a valid decimal
 * This intended for use when validating that a value can be stored in a database as a decimal
 *
 * Example of how digits are stored in the database
 * Decimal(5,2) is allowed a total of 5 digits, and will always round to 2 decimal places
 * This means it has a maximum 3 digits before the decimal point
 *
 * Valid
 * 123.99
 * 999.99
 * -999.99
 * 123.999 - will round to 124.00
 *
 * Not valid
 * 1234.9 - 4 digits the before the decimal point
 * 999.999 - would be rounded to 1000.00 which exceeds 5 total digits
 */
class DecimalFieldValidator extends NumericFieldValidator
{
    /**
     * Whole number size e.g. For Decimal(9,2) this would be 9
     */
    private int $wholeSize;

    /**
     * Decimal size e.g. For Decimal(5,2) this would be 2
     */
    private int $decimalSize;

    public function __construct(string $name, mixed $value, int $wholeSize, int $decimalSize)
    {
        parent::__construct($name, $value);
        $this->wholeSize = $wholeSize;
        $this->decimalSize = $decimalSize;
    }

    protected function validateValue(): ValidationResult
    {
        $result = parent::validateValue();
        if (!$result->isValid()) {
            return $result;
        }
        // Convert to absolute value - the minus sign is not relevant for validation
        $absValue = abs($this->value);
        // Round to the decimal size which is what the database will do
        $rounded = round($absValue, $this->decimalSize);
        // Get formatted as a string, which will right pad with zeros to the decimal size
        $rounded = number_format($rounded, $this->decimalSize, thousands_separator: '');
        // Count this number of digits - the minus 1 is for the decimal point
        $digitCount = strlen((string) $rounded) - 1;
        if ($digitCount > $this->wholeSize) {
            $message = _t(
                __CLASS__ . '.TOOLARGE',
                'Cannot have more than {wholeSize} digits, including after the decimal point',
                ['wholeSize' => $this->wholeSize]
            );
            $result->addFieldError($this->name, $message);
        }
        return $result;
    }
}
