<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Staff extends DataObject implements TestOnly
{
    private static $db = array(
        'Salary' => 'BigInt',
    );

    private static $table_name = 'DataObjectTest_Staff';

    private static $has_one = array(
        'CurrentCompany' => Company::class,
        'PreviousCompany' => Company::class
    );
}
