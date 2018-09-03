<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldFilterHeaderTest_Team';

    private static $summary_fields = array(
        'Name' => 'Name',
        'City.Initial' => 'City',
        'Cheerleader.Hat.Colour' => 'Cheerleader Hat'
    );

    private static $db = array(
        'Name' => 'Varchar',
        'City' => 'Varchar'
    );

    private static $has_one = array(
        'Cheerleader' => Cheerleader::class,
        'CheerleadersMom' => Mom::class
    );
}
