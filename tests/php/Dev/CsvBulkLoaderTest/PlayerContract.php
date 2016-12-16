<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class PlayerContract extends DataObject implements TestOnly
{
    private static $table_name = 'CsvBulkLoaderTest_PlayerContract';

    private static $db = array(
        'Amount' => 'Currency',
    );
}
