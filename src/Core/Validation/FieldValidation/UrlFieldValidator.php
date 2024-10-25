<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Url;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorTrait;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorInterface;

/**
 * Validates that a value is a valid URL
 * Uses Symfony's Url constraint to validate
 */
class UrlFieldValidator extends StringFieldValidator implements SymfonyFieldValidatorInterface
{
    use SymfonyFieldValidatorTrait;

    public function getConstraint(): Constraint|array
    {
        $message =  _t(__CLASS__ . '.INVALID', 'Invalid URL');
        return new Url(message: $message);
    }
}
