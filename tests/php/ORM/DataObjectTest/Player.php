<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\DataObjectTest;
use SilverStripe\Security\Member;

class Player extends Member implements TestOnly
{
    private static $table_name = 'DataObjectTest_Player';

    private static $db = array(
        'IsRetired' => 'Boolean',
        'ShirtNumber' => 'Varchar',
    );

    private static $has_one = array(
        'FavouriteTeam' => DataObjectTest\Team::class,
    );

    private static $belongs_many_many = array(
        'Teams' => DataObjectTest\Team::class
    );

    private static $has_many = array(
        'Fans' => Fan::class . '.Favourite', // Polymorphic - Player fans
        'CaptainTeams' => Team::class . '.Captain',
        'FoundingTeams' => Team::class . '.Founder'
    );

    private static $belongs_to = array(
        'CompanyOwned' => Company::class . '.Owner'
    );

    private static $searchable_fields = array(
        'IsRetired',
        'ShirtNumber'
    );
}
