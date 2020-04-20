<?php

namespace SilverStripe\ORM\Tests\DBCompositeTest;

class SubclassedDBFieldObject extends TestObject
{
    private static $table_name = 'DBCompositeTest_SubclassedDBFieldObject';

    private static $db = [
        'OtherField' => 'Text',
        'OtherMoney' => 'Money',
        'OverriddenMoney' => 'Money'
    ];
}
