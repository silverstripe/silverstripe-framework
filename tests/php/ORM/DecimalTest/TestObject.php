<?php

namespace SilverStripe\ORM\Tests\DecimalTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'DecimalTest_DataObject';

    private static $db = array(
        'Name' => 'Varchar',
        'MyDecimal1' => 'Decimal',
        'MyDecimal2' => 'Decimal(5,3,2.5)',
        'MyDecimal3' => 'Decimal(4,2,"Invalid default value")',
        'MyDecimal4' => 'Decimal'
    );

    private static $defaults = array(
        'MyDecimal4' => 4
    );
}
