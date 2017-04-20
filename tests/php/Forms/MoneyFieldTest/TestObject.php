<?php

namespace SilverStripe\Forms\Tests\MoneyFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'MoneyFieldTest_Object';

    private static $db = array(
        'MyMoney' => 'Money',
    );
}
