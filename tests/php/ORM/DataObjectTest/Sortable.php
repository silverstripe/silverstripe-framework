<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Sortable extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Sortable';

    private static $db = array(
        'Sort' => 'Int',
        'Name' => 'Varchar',
    );
}
