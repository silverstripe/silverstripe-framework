<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\FieldValidation\FieldValidator;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Validates that a value is a valid date, which means that it follows the equivalent formats:
 * - PHP date format Y-m-d
 * - SO format y-MM-dd i.e. DBDate::ISO_DATE
 *
 * Blank string values are allowed
 */
class DateFieldValidator extends FieldValidator
{
    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        // Allow empty strings
        if ($this->value === '') {
            return $result;
        }
        // Not using symfony/validator because it was allowing d-m-Y format strings
        $date = date_parse_from_format($this->getFormat(), $this->value ?? '');
        if ($date === false || $date['error_count'] > 0 || $date['warning_count'] > 0) {
            $result->addFieldError($this->name, $this->getMessage());
        }
        return $result;
    }

    protected function getFormat(): string
    {
        return 'Y-m-d';
    }

    protected function getMessage(): string
    {
        return _t(__CLASS__ . '.INVALID', 'Invalid date');
    }
}
