<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ManyManyList Teams()
 */
class Player extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldTest_Player';

    private static $db = array(
        'Name' => 'Varchar',
        'Email' => 'Varchar',
    );

    private static $belongs_many_many = [
        'Teams' => Team::class
    ];
}
