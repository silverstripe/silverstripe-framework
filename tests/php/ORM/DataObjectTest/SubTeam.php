<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;

class SubTeam extends Team implements TestOnly
{
    private static $table_name = 'DataObjectTest_SubTeam';

    private static $db = array(
        'SubclassDatabaseField' => 'Varchar'
    );

    private static $has_one = array(
        "ParentTeam" => Team::class,
    );

    private static $many_many = array(
        'FormerPlayers' => Player::class
    );

    private static $many_many_extraFields = array(
        'FormerPlayers' => array(
            'Position' => 'Varchar(100)'
        )
    );
}
