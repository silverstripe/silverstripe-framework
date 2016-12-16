<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectF extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_F';

    private static $db = array(
        'SortOrder' => 'Int',
        'MyDate' => 'Datetime',
        'MyString' => 'Text'
    );
}
