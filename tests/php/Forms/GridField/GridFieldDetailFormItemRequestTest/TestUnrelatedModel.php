<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * A DataObject that lives outside the list's class hierarchy. Used to assert
 * that a ClassName change cannot convert a record to an unrelated type.
 */
class TestUnrelatedModel extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormItemRequestTest_TestUnrelatedModel';

    private static $db = [
        'Name' => 'Varchar',
    ];

    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return true;
    }
}
