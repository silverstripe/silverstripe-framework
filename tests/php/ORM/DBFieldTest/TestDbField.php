<?php

namespace SilverStripe\ORM\Tests\DBFieldTest;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;

class TestDbField extends DBField implements TestOnly
{
    public function requireField()
    {
        // Basically the same as DBVarchar but we don't want to test with DBVarchar in case something
        // changes in that class eventually.
        $charset = Config::inst()->get(MySQLDatabase::class, 'charset');
        $collation = Config::inst()->get(MySQLDatabase::class, 'collation');

        $parts = [
            'datatype' => 'varchar',
            'precision' => 255,
            'character set' => $charset,
            'collate' => $collation,
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'varchar',
            'parts' => $parts
        ];

        DB::require_field($this->tableName, $this->name, $values);
    }

    public $saveIntoCalledCount = 0;

    public function saveInto($dataObject)
    {
        $this->saveIntoCalledCount++;
        return parent::saveInto($dataObject);
    }
}
