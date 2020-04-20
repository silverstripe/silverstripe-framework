<?php

namespace SilverStripe\ORM\Tests\HasManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Employee extends DataObject implements TestOnly
{
    private static $db = [
        'Name' => 'Varchar(100)',
    ];

    private static $has_one = [
        'Company' => Company::class,
    ];

    private static $has_many = [
        'CompanyCars' => CompanyCar::class,
    ];
}
