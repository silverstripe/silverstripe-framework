<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;

class TestAllowedChildrenE extends HierarchyModel implements TestOnly
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenE';

    // Only allows nothing
    private static $allowed_children = 'none';
}
