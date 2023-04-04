<?php

namespace SilverStripe\ORM\Tests\DBSchemaManagerTest;

class GrandChildClass extends ChildClass
{
    private static $table_name = 'DBSchemaManagerTest_GrandChildClass';

    private static $db = [
        'GrandChildField' => 'Varchar',
    ];
}
