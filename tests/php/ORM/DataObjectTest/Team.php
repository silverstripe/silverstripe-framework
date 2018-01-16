<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;

/**
 * @property string Title
 * @property string DatabaseField
 * @method Player Captain()
 * @method Player Founder()
 * @method Player HasOneRelationship()
 * @method HasManyList SubTeams()
 * @method HasManyList Comments()
 * @method HasManyList Fans()
 * @method HasManyList PlayerFans()
 * @method ManyManyList Players()
 * @method ManyManyList Sponsors()
 * @method ManyManyList EquipmentSuppliers()
 */
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
        'Fans' => Fan::class . '.Favourite', // Polymorphic - Team fans
        'PlayerFans' => Player::class . '.FavouriteTeam'
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
        'Sponsors' => EquipmentCompany::class . '.SponsoredTeams',
        'EquipmentSuppliers' => EquipmentCompany::class . '.EquipmentCustomers'
    );

    private static $summary_fields = array(
        'Title', // Overridden by Team_Extension
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
