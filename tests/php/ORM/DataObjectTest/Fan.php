<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Fan extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Fan';

    private static $db = array(
        'Name' => 'Varchar(255)',
        'Email' => 'Varchar',
    );

    private static $has_one = array(
        'Favourite' => DataObject::class, // Polymorphic relation
        'SecondFavourite' => DataObject::class
    );
}
