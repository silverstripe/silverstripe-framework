<?php

namespace SilverStripe\ORM\Tests\MySQLDatabaseTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Data extends DataObject implements TestOnly
{
    private static $table_name = 'MySQLDatabaseTest_Data';

    private static $db = array(
        'Title' => 'Varchar',
        'Description' => 'Text',
        'Enabled' => 'Boolean',
        'Sort' => 'Int'
    );

    private static $default_sort = '"Sort" ASC';
}
