<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorTrait;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorInterface;

/**
 * Validates that a value is a valid email address
 * Uses Symfony's Email constraint to validate
 */
class EmailFieldValidator extends StringFieldValidator implements SymfonyFieldValidatorInterface
{
    use SymfonyFieldValidatorTrait;

    public function getConstraint(): Constraint|array
    {
        $message =  _t(__CLASS__ . '.INVALID', 'Invalid email address');
        return new Email(message: $message);
    }
}
