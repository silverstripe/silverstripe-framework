<?php

namespace SilverStripe\ORM\Tests\DecimalTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'DecimalTest_DataObject';

    private static $db = [
        'Name' => 'Varchar',
        'MyDecimal1' => 'Decimal',
        'MyDecimal2' => 'Decimal(5,3,2.5)',
        'MyDecimal4' => 'Decimal',
        'MyDecimal5' => 'Decimal(20,18,0.99999999999999999999)',
        'MyDecimal6' => 'Decimal',
    ];

    private static $defaults = [
        'MyDecimal4' => 4,
        'MyDecimal6' => 7.99999999999999999999,
    ];
}
