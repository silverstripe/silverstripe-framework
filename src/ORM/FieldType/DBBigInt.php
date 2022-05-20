<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DB;

/**
 * Represents a signed 8 byte integer field. Do note PHP running as 32-bit might not work with Bigint properly, as it
 * would convert the value to a float when queried from the database since the value is a 64-bit one.
 *
 * @package framework
 * @subpackage model
 * @see Int
 */
class DBBigInt extends DBInt
{

    public function requireField()
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
