<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectI extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_I';

    private static $db = [
        'Name' => 'Varchar',
        'SortOrder' => 'Int',
    ];
}
