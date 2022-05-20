<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBVarchar;

class Fan extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Fan';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Email' => DBVarchar::class
    ];

    private static $has_one = [
        'Favourite' => DataObject::class, // Polymorphic relation
        'SecondFavourite' => DataObject::class
    ];
}
