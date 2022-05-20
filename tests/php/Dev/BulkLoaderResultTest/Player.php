<?php

namespace SilverStripe\Dev\Tests\BulkLoaderResultTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Player extends DataObject implements TestOnly
{
    private static $table_name = 'BulkLoaderTestPlayer';

    private static $db = [
        'Name' => 'Varchar',
        'Status' => 'Varchar',
    ];
}
