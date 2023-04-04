<?php

namespace SilverStripe\ORM\Tests\DBSchemaManagerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class BaseClass extends DataObject implements TestOnly
{
    private static $table_name = 'DBSchemaManagerTest_BaseClass';

    private static $db = [
        'BaseClassField' => 'Varchar',
    ];
}
