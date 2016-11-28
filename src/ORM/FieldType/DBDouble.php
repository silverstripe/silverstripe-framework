<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\MySQLDatabase;

/**
 * Supports double precision DB types
 */
class DBDouble extends DBFloat
{

    public function requireField()
    {
        // HACK: MSSQL does not support double so we're using float instead
        // @todo This should go into MSSQLDatabase ideally somehow
        if (DB::get_conn() instanceof MySQLDatabase) {
            DB::require_field($this->tableName, $this->name, "double");
        } else {
            DB::require_field($this->tableName, $this->name, "float");
        }
    }
}
