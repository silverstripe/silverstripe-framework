<?php

namespace SilverStripe\ORM\Tests\HasManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Company extends DataObject implements TestOnly
{
    private static $db = array(
        'Name' => 'Varchar(100)',
    );

    private static $has_many = array(
        'Employees' => Employee::class,
        'CompanyCars' => CompanyCar::class,
    );
}
