<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Team';

    private static $db = array(
        'Title' => 'Varchar',
        'DatabaseField' => 'HTMLVarchar'
    );

    private static $has_one = array(
        "Captain" => Player::class,
        "Founder" => Player::class,
        'HasOneRelationship' => Player::class,
    );

    private static $has_many = array(
        'SubTeams' => SubTeam::class,
        'Comments' => TeamComment::class,
        'Fans' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Fan.Favourite', // Polymorphic - Team fans
        'PlayerFans' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Player.FavouriteTeam'
    );

    private static $many_many = array(
        'Players' => Player::class
    );

    private static $many_many_extraFields = array(
        'Players' => array(
            'Position' => 'Varchar(100)'
        )
    );

    private static $belongs_many_many = array(
        'Sponsors' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\EquipmentCompany.SponsoredTeams',
        'EquipmentSuppliers' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\EquipmentCompany.EquipmentCustomers'
    );

    private static $summary_fields = array(
        'Title' => 'Custom Title',
        'Title.UpperCase' => 'Title',
        'Captain.ShirtNumber' => 'Captain\'s shirt number',
        'Captain.FavouriteTeam.Title' => 'Captain\'s favourite team'
    );

    private static $default_sort = '"Title"';

    private static $extensions = [
        Team_Extension::class
    ];

    public function MyTitle()
    {
        return 'Team ' . $this->Title;
    }

    public function getDynamicField()
    {
        return 'dynamicfield';
    }
}
