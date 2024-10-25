<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\Core\Validation\FieldValidation\IpFieldValidator;

class DBIp extends DBVarchar
{
    private static array $field_validators = [
        IpFieldValidator::class,
    ];
}
