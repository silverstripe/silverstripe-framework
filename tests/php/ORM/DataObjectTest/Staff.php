<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Staff extends DataObject implements TestOnly
{
    private static $db = [
        'Salary' => 'BigInt',
        'EmploymentType' => 'Varchar',
    ];

    private static $table_name = 'DataObjectTest_Staff';

    private static $has_one = [
        'CurrentCompany' => Company::class,
        'PreviousCompany' => Company::class
    ];

    private static $defaults = [
        'EmploymentType' => 'Staff',
    ];
}
