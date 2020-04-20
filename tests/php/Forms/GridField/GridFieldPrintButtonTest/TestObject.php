<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldPrintButtonTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldPrintButtonTest_Object';

    private static $db = [
        'Name' => 'Varchar'
    ];
}
