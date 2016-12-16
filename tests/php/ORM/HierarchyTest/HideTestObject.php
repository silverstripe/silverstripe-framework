<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 * @mixin Hierarchy
 */
class HideTestObject extends DataObject implements TestOnly
{
    private static $table_name = 'HierarchyHideTest_Object';

    private static $db = array(
        'Title' => 'Varchar'
    );

    private static $extensions = array(
        Hierarchy::class,
        Versioned::class,
    );

    public function cmstreeclasses()
    {
        return $this->markingClasses();
    }
}
