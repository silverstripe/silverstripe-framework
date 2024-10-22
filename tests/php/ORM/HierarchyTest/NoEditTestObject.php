<?php

namespace SilverStripe\ORM\Tests\HierarchyTest;

class NoEditTestObject extends TestObject
{
    private static $table_name = 'HierarchyHideTest_NoEditTestObject';

    public function canEdit($member = null)
    {
        return false;
    }
}
