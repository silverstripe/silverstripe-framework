<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldExportButtonTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldExportButtonTest_Team';

    private static $db = array(
        'Name' => 'Varchar',
        'City' => 'Varchar',
        'RugbyTeamNumber' => 'Int'
    );

    public function canView($member = null)
    {
        return true;
    }
}
