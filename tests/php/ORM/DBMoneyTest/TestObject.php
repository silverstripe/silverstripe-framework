<?php

namespace SilverStripe\ORM\Tests\DBMoneyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'MoneyTest_DataObject';

    private static $db = [
        'MyMoney' => 'Money',
    ];
}
