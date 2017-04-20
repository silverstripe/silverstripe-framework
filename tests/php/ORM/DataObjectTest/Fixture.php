<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Fixture extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Fixture';

    private static $db = array(
        // Funny field names
        'Data' => 'Varchar',
        'Duplicate' => 'Varchar',
        'DbObject' => 'Varchar',

        // Field types
        'DateField' => 'Date',
        'DatetimeField' => 'Datetime',

        'MyFieldWithDefault' => 'Varchar',
        'MyFieldWithAltDefault' => 'Varchar'
    );

    private static $defaults = array(
        'MyFieldWithDefault' => 'Default Value',
    );

    private static $summary_fields = array(
        'Data' => 'Data',
        'DateField.Nice' => 'Date'
    );

    private static $searchable_fields = array();

    public function populateDefaults()
    {
        parent::populateDefaults();

        $this->MyFieldWithAltDefault = 'Default Value';
    }
}
