<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Book extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Book';

    private static $db = array(
        'Title' => 'Varchar',
        'Summary' => 'Varchar'
    );
}
