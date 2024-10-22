<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\HiddenClass;

class TestAllowedChildrenHidden extends HierarchyModel implements TestOnly, HiddenClass
{
    private static $table_name = 'HierarchyTest_TestAllowedChildrenHidden';
}
