<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaGenerationTest_DO';

    private static $db = array(
        'Enum1' => 'Enum("A, B, C, D","")',
        'Enum2' => 'Enum("A, B, C, D","A")',
        'NumberField' => 'Decimal',
        'FloatingField' => 'Decimal(10,3,1.1)',
        'TextValue' => 'Varchar',
        'Date' => 'Datetime',
        'MyNumber' => 'Int'
    );
}
