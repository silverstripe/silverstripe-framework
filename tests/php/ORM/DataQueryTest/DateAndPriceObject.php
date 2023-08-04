<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DateAndPriceObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_DateAndPriceObject';

    private static $db = [
        'Date' => 'Date',
        'Price' => 'Int',
    ];
}
