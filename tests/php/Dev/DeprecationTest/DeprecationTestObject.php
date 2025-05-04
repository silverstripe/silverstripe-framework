<?php

namespace SilverStripe\Dev\Tests\DeprecationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\Deprecation;

class DeprecationTestObject extends DataObject implements TestOnly
{
    public function __construct()
    {
        parent::__construct();
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice(
                '1.2.3',
                'Some class message',
                Deprecation::SCOPE_CLASS
            );
        });
    }

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
