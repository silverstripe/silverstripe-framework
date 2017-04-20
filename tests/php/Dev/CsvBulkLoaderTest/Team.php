<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'TeamSize' => 'Int',
    );

    private static $has_many = array(
        'Players' => Player::class,
    );

    private static $table_name = 'CsvBulkLoaderTest_Team';
}
