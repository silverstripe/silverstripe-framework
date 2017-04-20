<?php

namespace SilverStripe\ORM\Tests\SQLInsertTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class SQLInsertTestBase extends DataObject implements TestOnly
{
    private static $table_name = 'SQLInsertTestBase';

    private static $db = array(
        'Title' => 'Varchar(255)',
        'HasFun' => 'Boolean',
        'Age' => 'Int',
        'Description' => 'Text',
    );
}
