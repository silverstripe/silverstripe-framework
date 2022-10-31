<?php

namespace SilverStripe\Dev\Tests\DeprecationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DeprecationTestObject extends DataObject implements TestOnly
{
    private static $db = [
        "Name" => "Varchar"
    ];

    private static $table_name = 'DeprecatedTestObject';

    /**
     * @deprecated 1.2.3 My first config message
     */
    private static $first_config = 'ABC';

    /**
     * @deprecated My second config message
     */
    private static $second_config = 'DEF';

    /**
     * @deprecated
     */
    private static $third_config = 'XYZ';

    /**
     * @deprecated 1.2.3 My array config message
     */
    private static $array_config = ['lorem', 'ipsum'];
}
