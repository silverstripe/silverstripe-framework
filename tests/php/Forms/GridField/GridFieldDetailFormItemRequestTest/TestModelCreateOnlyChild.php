<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;

/**
 * A subclass within the list's hierarchy that the user may create but may NOT edit.
 * Used to assert that converting a record into this type is rejected - the conversion
 * writes the submitted form data into the record, so it requires edit permission too.
 */
class TestModelCreateOnlyChild extends TestModel implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormItemRequestTest_TestModelCreateOnlyChild';

    public function canEdit($member = null)
    {
        return false;
    }
}
