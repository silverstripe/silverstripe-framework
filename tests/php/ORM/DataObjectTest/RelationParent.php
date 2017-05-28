<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class RelationParent extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_RelationParent';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];
}
