<?php

namespace SilverStripe\ORM\Tests\SQLUpdateTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestOther extends DataObject implements TestOnly
{
    private static $table_name = 'SQLUpdateTestOther';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Description' => 'Text'
    ];
}
