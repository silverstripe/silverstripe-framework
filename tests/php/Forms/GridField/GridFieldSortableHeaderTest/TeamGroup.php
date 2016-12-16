<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest;

use SilverStripe\Dev\TestOnly;

class TeamGroup extends Team implements TestOnly
{
    private static $table_name = 'GridFieldSortableHeaderTest_TeamGroup';

    private static $db = array(
        'GroupName' => 'Varchar'
    );
}
