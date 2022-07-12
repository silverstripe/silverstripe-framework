<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class OverriddenDataObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_OverriddenDataObject';

    private static $db = [
        'Salary' => 'BigInt',
        'EmploymentType' => 'Varchar',
    ];

    private static $has_one = [
        'CurrentCompany' => Company::class,
    ];
}
