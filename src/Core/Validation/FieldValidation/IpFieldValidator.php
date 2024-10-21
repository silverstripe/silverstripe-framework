<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraints;
use SilverStripe\Core\Validation\FieldValidation\AbstractSymfonyFieldValidator;

/**
 * Validator for IP addresses. Accepts both IPv4 and IPv6.
 */
class IpFieldValidator extends AbstractSymfonyFieldValidator
{
    protected function getConstraintClass(): string
    {
        return Constraints\Ip::class;
    }

    protected function getContraintNamedArgs(): array
    {
        return [
            // Allow both IPv4 and IPv6
            'version' => Constraints\Ip::ALL,
        ];
    }

    protected function getMessage(): string
    {
        return _t(__CLASS__ . '.INVALID', 'Invalid IP address');
    }
}
