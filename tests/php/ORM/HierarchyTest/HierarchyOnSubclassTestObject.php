<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * @mixin Versioned
 */
class HierarchyOnSubclassTestObject extends DataObject implements TestOnly
{
    private static $table_name = 'HierarchyOnSubclassTest_Object';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $extensions = [
        Versioned::class,
    ];

    private static $default_sort = 'Title ASC';

    public function cmstreeclasses()
    {
        return $this->markingClasses();
    }
}
