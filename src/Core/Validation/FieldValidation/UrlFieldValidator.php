<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraints;
use SilverStripe\Core\Validation\FieldValidation\AbstractSymfonyFieldValidator;

class UrlFieldValidator extends AbstractSymfonyFieldValidator
{
    protected function getConstraintClass(): string
    {
        return Constraints\Url::class;
    }

    protected function getMessage(): string
    {
        return _t(__CLASS__ . '.INVALID', 'Invalid URL');
    }
}
