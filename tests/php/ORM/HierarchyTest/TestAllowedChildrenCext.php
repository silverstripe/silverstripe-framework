<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;

class TestAllowedChildrenCext extends TestAllowedChildrenC implements TestOnly
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenCext';

    // Override TestAllowedChildrenC definitions
    private static $allowed_children = [
        TestAllowedChildrenB::class,
    ];
}
