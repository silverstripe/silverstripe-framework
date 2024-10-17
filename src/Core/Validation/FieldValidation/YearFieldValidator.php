<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;

/**
 * Validates that a field is an integer year between two dates, or 0 for a null value.
 */
class YearFieldValidator extends IntFieldValidator
{
    private ?int $minValue;

    public function __construct(
        string $name,
        mixed $value,
        ?int $minValue = null,
        ?int $maxValue = null
    ) {
        $this->minValue = $minValue;
        parent::__construct($name, $value, 0, $maxValue);
    }

    protected function validateValue(): ValidationResult
    {
        $result = parent::validateValue();
        if ($this->value === 0) {
            return $result;
        }
        if ($this->minValue && $this->value < $this->minValue) {
            // Uses the same translation key as NumericFieldValidator
            $message = _t(
                NumericFieldValidator::class . '.TOOSMALL',
                'Value cannot be less than {minValue}',
                ['minValue' => $this->minValue]
            );
            $result->addFieldError($this->name, $message, value: $this->value);
        }
        return $result;
    }
}
