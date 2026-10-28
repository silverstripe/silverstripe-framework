<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;

/**
 * A subclass within the list's hierarchy that the user may edit but may NOT create.
 * Used to assert that converting an existing record into this type is rejected - the
 * conversion creates a record of this type, so it requires create permission as well.
 */
class TestModelEditOnlyChild extends TestModel implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormItemRequestTest_TestModelEditOnlyChild';

    public function canCreate($member = null, $context = [])
    {
        return false;
    }
}
