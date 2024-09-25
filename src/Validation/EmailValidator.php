<?php

namespace SilverStripe\Validation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Validation\FieldValidator;
use SilverStripe\Core\Validation\ConstraintValidator;
use Symfony\Component\Validator\Constraints;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBField;

class EmailValidator extends FieldValidator
{
    protected function validateValue(ValidationResult $result): ValidationResult
    {
        $message = _t('SilverStripe\\Forms\\EmailField.VALIDATION', 'Please enter an email address');
        $validationResult = ConstraintValidator::validate(
            $this->value,
            new Constraints\Email(message: $message),
            $this->name
        );
        return $result->combineAnd($validationResult);
    }
}
