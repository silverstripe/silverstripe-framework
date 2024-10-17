<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use InvalidArgumentException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\EnumFieldValidator;

class MultiEnumFieldValidator extends EnumFieldValidator
{
    public function __construct(string $name, mixed $value, array $allowedValues)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Value must be an array');
        }
        parent::__construct($name, $value, $allowedValues);
    }

    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        foreach ($this->value as $value) {
            if (!in_array($value, $this->allowedValues, true)) {
                $message = _t(__CLASS__ . '.NOTALLOWED', 'Not an allowed value');
                $result->addFieldError($this->name, $message, value: $value);
                break;
            }
        }
        return $result;
    }
}
