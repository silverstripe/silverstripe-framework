<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;

/**
 * @mixin Versioned
 * @mixin Hierarchy
 */
class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'HierarchyTest_Object';

    private static $db = array(
        'Title' => 'Varchar'
    );

    private static $extensions = array(
        Hierarchy::class,
        Versioned::class,
    );

    private static $default_sort = 'Title ASC';

    public function cmstreeclasses()
    {
        return $this->markingClasses();
    }
}
