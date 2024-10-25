<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\FieldValidator;
use InvalidArgumentException;

/**
 * Validates that a value is a numeric value
 * Optionally, can also check that the value is within a certain range
 */
class NumericFieldValidator extends FieldValidator
{
    /**
     * Minimum size of the number
     */
    private ?int $minValue;

    /**
     * Maximum size of the number
     */
    private ?int $maxValue;

    public function __construct(
        string $name,
        mixed $value,
        ?int $minValue = null,
        ?int $maxValue = null
    ) {
        if (!is_null($minValue) && !is_null($maxValue) && $maxValue < $minValue) {
            throw new InvalidArgumentException('maxValue cannot be less than minValue');
        }
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
        parent::__construct($name, $value);
    }

    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        if (!is_numeric($this->value) || is_string($this->value)) {
            // Must be a numeric value, though not as a numeric string
            $message = _t(__CLASS__ . '.WRONGTYPE', 'Must be numeric, and not a string');
            $result->addFieldError($this->name, $message);
        } elseif (!is_null($this->minValue) && $this->value < $this->minValue) {
            $message = _t(
                __CLASS__ . '.TOOSMALL',
                'Value cannot be less than {minValue}',
                ['minValue' => $this->minValue]
            );
            $result->addFieldError($this->name, $message);
        } elseif (!is_null($this->maxValue) && $this->value > $this->maxValue) {
            $message = _t(
                __CLASS__ . '.TOOLARGE',
                'Value cannot be greater than {maxValue}',
                ['maxValue' => $this->maxValue]
            );
            $result->addFieldError($this->name, $message);
        }
        return $result;
    }
}
