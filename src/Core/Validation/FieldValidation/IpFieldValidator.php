<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Ip;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorTrait;
use SilverStripe\Core\Validation\FieldValidation\SymfonyFieldValidatorInterface;

/**
 * Validates that a value is a valid IP address
 * Uses Symfony's Ip constraint to validate
 */
class IpFieldValidator extends StringFieldValidator implements SymfonyFieldValidatorInterface
{
    use SymfonyFieldValidatorTrait;

    public function getConstraint(): Constraint|array
    {
        $message =  _t(__CLASS__ . '.INVALID', 'Invalid IP address');
        return new Ip(
            version: Ip::ALL,
            message: $message
        );
    }
}
