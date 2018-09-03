<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest;

use SilverStripe\Dev\TestOnly;

class TeamGroup extends Team implements TestOnly
{
    private static $table_name = 'GridFieldFilterHeaderTest_TeamGroup';

    private static $db = array(
        'GroupName' => 'Varchar'
    );
}
