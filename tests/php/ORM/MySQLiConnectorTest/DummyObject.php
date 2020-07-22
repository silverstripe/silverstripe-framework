<?php
namespace SilverStripe\ORM\Tests\MySQLiConnectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DummyObject extends DataObject implements TestOnly
{
    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $table_name = 'MySQLiConnectorTest_DummyObject';
}
