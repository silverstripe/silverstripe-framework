<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldExportButtonTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class NoView extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldExportButtonTest_NoView';

    private static $db = array(
        'Name' => 'Varchar',
        'City' => 'Varchar',
        'RugbyTeamNumber' => 'Int'
    );

    public function canView($member = null)
    {
        return false;
    }
}
