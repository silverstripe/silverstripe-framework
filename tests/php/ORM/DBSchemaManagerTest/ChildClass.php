<?php

namespace SilverStripe\ORM\Tests\DBSchemaManagerTest;

class ChildClass extends BaseClass
{
    private static $table_name = 'DBSchemaManagerTest_ChildClass';

    private static $db = [
        'ChildField' => 'Varchar',
    ];
}
