<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldTest_Team';

    private static $db = array(
        'Name' => 'Varchar',
        'City' => 'Varchar'
    );

    private static $many_many = [
        'Players' => Player::class
    ];

    private static $has_many = [
        'Cheerleaders' => Cheerleader::class
    ];

    private static $searchable_fields = array(
        'Name',
        'City',
        'Cheerleaders.Name'
    );

    public function canView($member = null)
    {
        return true;
    }
}
