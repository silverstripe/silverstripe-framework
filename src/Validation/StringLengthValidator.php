<?php

namespace SilverStripe\Validation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Validation\FieldValidator;

class StringLengthValidator extends FieldValidator
{
    private ?int $minLength;
    private ?int $maxLength;

    public function __construct(string $name, mixed $value, ?int $minLength = null, ?int $maxLength = null)
    {
        parent::__construct($name, $value);
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    protected function validateValue(ValidationResult $result): ValidationResult
    {
        if (!is_null($this->maxLength) && mb_strlen($this->value ?? '') > $this->maxLength) {
            $message = _t(
                'SilverStripe\\Forms\\TextField.VALIDATEMAXLENGTH',
                'The value for {name} must not exceed {maxLength} characters in length',
                ['name' => $this->name, 'maxLength' => $this->maxLength]
            );
            $result->addFieldError($this->name, $message);
        }
        // TODO: minlength check
        return $result;
    }
}
