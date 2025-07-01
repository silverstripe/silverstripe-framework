<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DB;

/**
 * Supports double precision DB types
 */
class DBDouble extends DBFloat
{
    /**
     * Get the specifications which will be used to generate this column in the database.
     */
    public function getFieldSpec(): string
    {
        // HACK: MSSQL does not support double so we're using float instead
        if (DB::get_conn() instanceof MySQLDatabase) {
            return 'double';
        }
        return 'float';
    }
}
