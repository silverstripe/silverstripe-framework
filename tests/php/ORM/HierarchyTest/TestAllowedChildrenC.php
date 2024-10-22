<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;

class TestAllowedChildrenC extends HierarchyModel implements TestOnly
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenC';

    private static $allowed_children = [
        TestAllowedChildrenA::class,
        TestAllowedChildrenD::class,
    ];
}
