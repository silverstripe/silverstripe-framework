<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;

/**
 * Validates that a value is a 32-bit signed integer
 */
class IntFieldValidator extends NumericFieldValidator
{
    /**
     * The minimum value for a signed 32-bit integer.
     * Defined as string instead of int because be cast to a float
     * on 32-bit systems if defined as an int
     */
    private const MIN_32_BIT_INT = '-2147483648';

    /**
     * The maximum value for a signed 32-bit integer.
     */
    private const MAX_32_BIT_INT = '2147483647';

    public function __construct(
        string $name,
        mixed $value,
        ?int $minValue = null,
        ?int $maxValue = null
    ) {
        if (is_null($minValue)) {
            $minValue = (int) IntFieldValidator::MIN_32_BIT_INT;
        }
        if (is_null($maxValue)) {
            $maxValue = (int) IntFieldValidator::MAX_32_BIT_INT;
        }
        parent::__construct($name, $value, $minValue, $maxValue);
    }

    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        if (!is_int($this->value)) {
            $message = _t(__CLASS__ . '.WRONGTYPE', 'Must be an integer');
            $result->addFieldError($this->name, $message);
        }
        if (!$result->isValid()) {
            return $result;
        }
        $result->combineAnd(parent::validateValue());
        return $result;
    }
}
