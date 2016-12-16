<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class BaseDataClass extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaTest_BaseDataClass';

    private static $db = array(
        'Title' => 'Varchar'
    );
}
