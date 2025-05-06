<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;

class TestObjectHideFromHierarchy extends TestObject implements TestOnly
{
    private static $table_name = 'HierarchyTest_ObjectHideFromHierarchy';

    private static $db = [
        'SomeField' => 'Varchar'
    ];
}
