<?php

namespace SilverStripe\ORM\Tests\Filters\ExactMatchFilterTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Task extends DataObject implements TestOnly
{
    private static $table_name = 'ExactMatchFilterTest_Task';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Project' => Project::class,
    ];
}
