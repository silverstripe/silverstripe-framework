<?php

namespace SilverStripe\ORM\Tests\TransactionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'TransactionTest_Object';

    private static $db = array(
        'Title' => 'Varchar(255)'
    );
}
