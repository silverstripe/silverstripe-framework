<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldPaginatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CanViewCheckObject extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldPaginatorTest_CanViewCheckObject';

    private static $db = [
        'Name' => 'Varchar'
    ];

    public static bool $canView = true;

    public function canView($member = null)
    {
        static::$canView = !static::$canView;
        return static::$canView;
    }
}
