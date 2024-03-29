<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 *
 * @method ManyManyList Players()
 */
class Team extends DataObject implements TestOnly
{
    private static $table_name = 'FormTest_Team';

    private static $db = [
        'Name' => 'Varchar',
        'Region' => 'Varchar',
    ];

    private static $many_many = [
        'Players' => Player::class
    ];
}
