<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Permissions extends DataObject implements TestOnly
{

    private static $table_name = 'GridFieldTest_Permissions';

    private static $db = array(
        'Name' => 'Varchar',
        'Email' => 'Varchar',
    );

    private static $summary_fields = array(
        'Name',
        'Email'
    );

    public function canView($member = null)
    {
        // Only records with odd numbers are viewable
        if (!($this->ID % 2)) {
            return false;
        }
        return true;
    }
}
