<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;

class TestAllowedChildrenB extends HierarchyModel implements TestOnly
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenB';

    // Also allowed subclasses
    private static $allowed_children = [
        TestAllowedChildrenC::class,
    ];
}
