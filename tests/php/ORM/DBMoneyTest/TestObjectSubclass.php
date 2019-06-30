<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DBMoneyTest;

use SilverStripe\Dev\TestOnly;

class TestObjectSubclass extends TestObject implements TestOnly
{
    private static $table_name = 'MoneyTest_SubClass';

    private static $db = [
        'MyOtherMoney' => 'Money',
    ];
}
