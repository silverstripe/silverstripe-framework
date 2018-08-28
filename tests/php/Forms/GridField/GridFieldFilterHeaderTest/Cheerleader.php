<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Cheerleader extends DataObject implements TestOnly
{

    private static $table_name = 'GridFieldFilterHeaderTest_Cheerleader';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $has_one = array(
        'Team' => Team::class,
        'Hat' => CheerleaderHat::class
    );
}
