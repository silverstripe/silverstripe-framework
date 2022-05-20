<?php

namespace SilverStripe\ORM\Tests\ComponentSetTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $table_name = 'ComponentSetTest_Team';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $many_many = [
        'Players' => Player::class
    ];
}
