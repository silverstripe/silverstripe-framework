<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\ValidationInterface;

/**
 * Abstract class that can be used as a validator for FormFields and DBFields
 */
abstract class FieldValidator implements ValidationInterface
{
    protected string $name;
    protected mixed $value;

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
        $validationResult = $this->validateValue($result);
        if (!$validationResult->isValid()) {
            $result->combineAnd($validationResult);
        }
        return $result;
    }

    /**
     * Inner validatation method that that is implemented by subclasses
     */
    abstract protected function validateValue(): ValidationResult;
}
