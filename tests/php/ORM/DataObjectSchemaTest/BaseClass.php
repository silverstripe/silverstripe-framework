<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class BaseClass extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaTest_BaseClass';
    private static $db = [
        'Title' => 'Varchar',
    ];
}
