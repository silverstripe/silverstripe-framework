<?php

namespace SilverStripe\ORM\Tests\DBMoneyTest;

use SilverStripe\Dev\TestOnly;

class TestObjectSubclass extends TestObject implements TestOnly
{
    private static $table_name = 'MoneyTest_SubClass';

    private static $db = [
        'MyOtherMoney' => 'Money',
    ];
}
