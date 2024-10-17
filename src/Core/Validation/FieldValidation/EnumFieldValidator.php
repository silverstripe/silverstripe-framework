<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\FieldValidator;

class EnumFieldValidator extends FieldValidator
{
    protected array $allowedValues;

    public function __construct(string $name, mixed $value, array $allowedValues)
    {
        parent::__construct($name, $value);
        $this->allowedValues = $allowedValues;
    }

    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        // Allow empty strings
        if ($this->value === '') {
            return $result;
        }
        if (!in_array($this->value, $this->allowedValues, true)) {
            $message = _t(__CLASS__ . '.NOTALLOWED', 'Not an allowed value');
            $result->addFieldError($this->name, $message, value: $this->value);
        }
        return $result;
    }
}
