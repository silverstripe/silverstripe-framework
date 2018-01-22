<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Company extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Company';

    private static $db = [
        'Name' => 'Varchar'
    ];

    private static $has_one = [
        'CEO' => CEO::class,
        'PreviousCEO' => CEO::class,
        'Owner' => DataObject::class // polymorphic
    ];

    private static $has_many = array(
        'CurrentStaff' => Staff::class . '.CurrentCompany',
        'PreviousStaff' => Staff::class . '.PreviousCompany'
    );
}
