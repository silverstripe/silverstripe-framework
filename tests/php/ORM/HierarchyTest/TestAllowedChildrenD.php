<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;

class TestAllowedChildrenD extends HierarchyModel implements TestOnly
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenD';

    // Only allows this class, no children classes
    private static $allowed_children = [
        '*' . TestAllowedChildrenC::class,
    ];
}
