<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;

/**
 * @mixin Versioned
 * @mixin Hierarchy
 */
class HideTestSubObject extends HideTestObject
{
    private static $table_name = 'HierarchyHideTest_SubObject';
}
