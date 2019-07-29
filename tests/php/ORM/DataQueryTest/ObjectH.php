<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectH extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_H';

    private static $db = array(
        'Name' => 'Varchar',
        'SortOrder' => 'Int',
    );

    private static $many_many = array(
        'ManyTestEs' => ObjectE::class,
        'ManyTestIs' => ObjectI::class,
    );
}
