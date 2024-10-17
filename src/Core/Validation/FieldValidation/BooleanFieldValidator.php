<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\FieldValidator;

/**
 * Validates value is boolean stored as an integer i.e. 1 or 0
 * true and false are not valid values
 */
class BooleanFieldValidator extends FieldValidator
{
    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        if ($this->value !== true && $this->value !== false) {
            $message = _t(__CLASS__ . '.INVALID', 'Invalid value');
            $result->addFieldError($this->name, $message, value: $this->value);
        }
        return $result;
    }
}
