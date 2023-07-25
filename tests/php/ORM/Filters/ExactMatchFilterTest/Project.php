<?php

namespace SilverStripe\ORM\Tests\Filters\ExactMatchFilterTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Project extends DataObject implements TestOnly
{
    private static $table_name = 'ExactMatchFilterTest_Project';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_many = [
        'Tasks' => Task::class,
    ];
}
