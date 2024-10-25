<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\FieldValidation\DateFieldValidator;

/**
 * Validates that a value is a valid date/time, which means that it follows the equivalent formats:
 * - PHP date format Y-m-d H:i:s
 * - ISO format 'y-MM-dd HH:mm:ss' i.e. DBDateTime::ISO_DATETIME
 *
 * Blank string values are allowed
 */
class DatetimeFieldValidator extends DateFieldValidator
{
    protected function getFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    protected function getMessage(): string
    {
        return _t(__CLASS__ . '.INVALID', 'Invalid date/time');
    }
}
