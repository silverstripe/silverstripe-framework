<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;

/**
 * A subclass within the list's hierarchy that the user IS permitted to create
 * and edit. Used to assert that a legitimate in-hierarchy ClassName change still
 * succeeds and is not broken by the security fix.
 */
class TestModelAllowedChild extends TestModel implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormItemRequestTest_TestModelAllowedChild';
}
