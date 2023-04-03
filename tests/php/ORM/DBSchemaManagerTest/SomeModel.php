<?php

namespace SilverStripe\ORM\Tests\DBSchemaManagerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class SomeModel extends DataObject implements TestOnly
{
    private static $table_name = 'DBSchemaManagerTest_SomeModel';

    private static $db = [
        'SomeField' => 'Varchar',
    ];
}
