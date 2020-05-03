<?php

namespace SilverStripe\Tests\ORM\UniqueKey;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class River extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'UniqueKeyTest_River';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar',
    ];
}
