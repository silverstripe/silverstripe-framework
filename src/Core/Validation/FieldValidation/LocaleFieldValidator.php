<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Locale;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorTrait;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorInterface;

/**
 * Validates that a value is a valid locale
 * Uses Symfony's Locale constraint to validate
 */
class LocaleFieldValidator extends StringFieldValidator implements SymfonyFieldValidatorInterface
{
    use SymfonyFieldValidatorTrait;

    public function getConstraint(): Constraint|array
    {
        $message =  _t(__CLASS__ . '.INVALID', 'Invalid locale');
        return new Locale(message: $message);
    }
}
