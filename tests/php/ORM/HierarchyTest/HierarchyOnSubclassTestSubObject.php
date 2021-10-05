<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Hierarchy\Hierarchy;

/**
 * @mixin Hierarchy
 */
class HierarchyOnSubclassTestSubObject extends HierarchyOnSubclassTestObject implements TestOnly
{
    private static $table_name = 'HierarchyOnSubclassTest_SubObject';

    private static $extensions = [
        Hierarchy::class,
    ];
}
