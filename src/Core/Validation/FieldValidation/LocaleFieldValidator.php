<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraints;
use SilverStripe\Core\Validation\FieldValidation\AbstractSymfonyFieldValidator;

/**
 * Validates that a value is a valid locale, e.g. de, de_DE)
 */
class LocaleFieldValidator extends AbstractSymfonyFieldValidator
{
    protected function getConstraintClass(): string
    {
        return Constraints\Locale::class;
    }

    protected function getMessage(): string
    {
        return _t(__CLASS__ . '.INVALID', 'Invalid locale');
    }
}
