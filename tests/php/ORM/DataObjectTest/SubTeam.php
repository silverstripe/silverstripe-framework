<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;

class SubTeam extends Team implements TestOnly
{
    private static $table_name = 'DataObjectTest_SubTeam';

    private static $db = [
        'SubclassDatabaseField' => 'Varchar',
        'SubclassFieldWithOverride' => 'Varchar',
    ];

    private static $has_one = [
        "ParentTeam" => Team::class,
    ];

    private static $many_many = [
        'FormerPlayers' => Player::class
    ];

    private static $many_many_extraFields = [
        'FormerPlayers' => [
            'Position' => 'Varchar(100)'
        ]
    ];

    /**
     * Override the value of SubclassFieldWithOverride
     * @return string Suffixes " (override)" to SubclassFieldWithOverride value
     */
    public function getSubclassFieldWithOverride()
    {
        return $this->getField('SubclassFieldWithOverride') . ' (override)';
    }
}
