<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;

/**
 * A subclass within the list's hierarchy that the user is NOT permitted to
 * create or edit. Used to assert that a ClassName change re-checks permissions
 * against the new type rather than the original.
 */
class TestModelRestrictedChild extends TestModel implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormItemRequestTest_TestModelRestrictedChild';

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return false;
    }
}
