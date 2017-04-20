<?php

namespace SilverStripe\ORM\Tests\DBCompositeTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'DBCompositeTest_DataObject';

    private static $db = array(
        'Title' => 'Text',
        'MyMoney' => 'Money',
        'OverriddenMoney' => 'Money'
    );
}
