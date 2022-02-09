<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Address extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Address';

    private static $db = [
        'FirstName' => 'Text'
    ];
}
