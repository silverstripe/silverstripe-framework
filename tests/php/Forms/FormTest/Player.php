<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @skipUpgrade
 */
class Player extends DataObject implements TestOnly
{

    private static $table_name = 'FormTest_Player';

    private static $db = array(
        'Name' => 'Varchar',
        'Biography' => 'Text',
        'Birthday' => 'Date'
    );

    private static $belongs_many_many = array(
        'Teams' => Team::class
    );

    private static $has_one = array(
        'FavouriteTeam' => Team::class
    );

    public function getBirthdayYear()
    {
        return ($this->Birthday) ? date('Y', strtotime($this->Birthday)) : null;
    }
}
