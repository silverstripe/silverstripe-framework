<?php

namespace SilverStripe\ORM\Tests\HasManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CompanyCar extends DataObject implements TestOnly
{
    private static $db = [
        'Make' => 'Varchar(100)',
        'Model' => 'Varchar(100)',
    ];

    private static $has_one = [
        'User' => Employee::class,
        'Company' => Company::class,
    ];

    private static $default_sort = 'Make';
}
