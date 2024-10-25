<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Validation\FieldValidation\IntFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\BigIntFieldValidator;
use SilverStripe\ORM\DB;

/**
 * Represents a signed 8 byte integer field with a range between -9223372036854775808 and 9223372036854775807
 *
 * Do note PHP running as 32-bit might not work with Bigint properly, as it
 * would convert the value to a float when queried from the database since the value is a 64-bit one.
 */
class DBBigInt extends DBInt
{
    private static array $field_validators = [
        // Remove parent validator and add BigIntValidator instead
        IntFieldValidator::class => null,
        BigIntFieldValidator::class,
    ];

    public function requireField(): void
    {
        $parts = [
            'datatype' => 'bigint',
            'precision' => 8,
            'null' => 'not null',
            'default' => $this->defaultVal,
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'bigint', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }
}
