<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;

class TestAllowedChildrenA extends HierarchyModel implements TestOnly
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenA';

    private static $allowed_children = [
        TestAllowedChildrenB::class,
    ];
}
