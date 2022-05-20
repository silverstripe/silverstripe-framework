<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Fixture extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Fixture';

    private static $db = [
        // Funny field names
        'Data' => 'Varchar',
        'Duplicate' => 'Varchar',
        'DbObject' => 'Varchar',

        // Field types
        'DateField' => 'Date',
        'DatetimeField' => 'Datetime',

        'MyFieldWithDefault' => 'Varchar',
        'MyFieldWithAltDefault' => 'Varchar',

        'MyInt' => 'Int',
        'MyCurrency' => 'Currency',
        'MyDecimal'=> 'Decimal',

        // Enums
        'MyEnum' => 'Enum("25,50,75,100", "50")',
        'MyEnumWithDots' => 'Enum("25.25,50.00,75.00,100.50", "50.00")',
    ];

    private static $defaults = [
        'MyFieldWithDefault' => 'Default Value',
    ];

    private static $summary_fields = [
        'Data' => 'Data',
        'DateField.Nice' => 'Date'
    ];

    private static $searchable_fields = [];

    public function populateDefaults()
    {
        parent::populateDefaults();

        $this->MyFieldWithAltDefault = 'Default Value';
    }
}
