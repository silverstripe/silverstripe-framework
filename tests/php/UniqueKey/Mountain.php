<?php

namespace SilverStripe\Tests\UniqueKey;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Mountain extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'UniqueKeyTest_Mountain';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar',
    ];
}
