<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\ValidationInterface;

/**
 * Abstract class that can be used as a FieldValidator for FormFields and DBFields
 */
abstract class FieldValidator implements ValidationInterface
{
    /**
     * The name of the field being validated
     */
    protected string $name;

    /**
     * The value to validate
     */
    protected mixed $value;

    /**
     * Whether null is considered a valid value
     * Silverstripe fields are nullable by default
     */
    protected bool $allowNull = true;

    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Validate the value
     */
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        if (is_null($this->value) && $this->allowNull) {
            return $result;
        }
        $validationResult = $this->validateValue($result);
        if (!$validationResult->isValid()) {
            $result->combineAnd($validationResult);
        }
        return $result;
    }

    /**
     * Inner validation method that performs the actual validation logic
     */
    abstract protected function validateValue(): ValidationResult;
}
