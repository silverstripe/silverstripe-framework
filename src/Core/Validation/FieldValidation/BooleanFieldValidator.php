<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\FieldValidator;

/**
 * Validates that a value is a boolean
 */
class BooleanFieldValidator extends FieldValidator
{
    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        if (!is_bool($this->value)) {
            $message = _t(__CLASS__ . '.INVALID', 'Invalid value');
            $result->addFieldError($this->name, $message);
        }
        return $result;
    }
}
