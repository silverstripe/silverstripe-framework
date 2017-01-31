<?php

namespace SilverStripe\Admin\Tests\LeftAndMainTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'LeftAndMainTest_Object';

    private static $db = array(
        'Title' => 'Varchar',
        'URLSegment' => 'Varchar',
        'Sort' => 'Int',
    );

    private static $default_sort = '"Sort"';

    private static $extensions = [
        Hierarchy::class
    ];

    public function CMSTreeClasses()
    {
        return '';
    }
}
