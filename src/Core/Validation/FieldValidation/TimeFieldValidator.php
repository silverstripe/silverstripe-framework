<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\FieldValidation\DateFieldValidator;

/**
 * Validates that a value is a valid time, which means that it follows the equivalent formats:
 * - PHP date format H:i:s
 * - ISO format 'HH:mm:ss' i.e. DBTime::ISO_TIME
 *
 * Blank string values are allowed
 */
class TimeFieldValidator extends DateFieldValidator
{
    protected function getFormat(): string
    {
        return 'H:i:s';
    }

    protected function getMessage(): string
    {
        return _t(__CLASS__ . '.INVALID', 'Invalid time');
    }
}
