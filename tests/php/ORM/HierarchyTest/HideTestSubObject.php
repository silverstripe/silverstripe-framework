<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 * @mixin Hierarchy
 */
class HideTestSubObject extends HideTestObject
{
	private static $table_name = 'HierarchyHideTest_SubObject';
}
