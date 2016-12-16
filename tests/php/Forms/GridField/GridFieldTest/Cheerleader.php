<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Cheerleader extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldTest_Cheerleader';

    private static $db = [
        'Name' => 'Varchar'
    ];

    private static $has_one = [
        'Team' => Team::class,
    ];
}
