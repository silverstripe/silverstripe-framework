<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Customer extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Customer';

    private static $db = [
        'FirstName' => 'Text'
    ];
}
