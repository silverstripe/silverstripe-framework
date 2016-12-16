<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestMember extends DataObject implements TestOnly
{
    private static $table_name = 'DataExtensionTest_Member';

    private static $db = array(
        "Name" => "Varchar",
        "Email" => "Varchar"
    );

    private static $extensions = [
        ContactRole::class
    ];
}
