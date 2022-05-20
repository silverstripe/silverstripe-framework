<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TeamComment extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_TeamComment';

    private static $db = [
        'Name' => 'Varchar',
        'Comment' => 'Text'
    ];

    private static $has_one = [
        'Team' => Team::class
    ];

    private static $default_sort = '"Name" ASC';
}
