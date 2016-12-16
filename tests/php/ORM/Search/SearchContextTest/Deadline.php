<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Deadline extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Deadline';

    private static $db = array(
        'CompletionDate' => 'Datetime'
    );

    private static $has_one = array(
        'Project' => Project::class,
    );
}
