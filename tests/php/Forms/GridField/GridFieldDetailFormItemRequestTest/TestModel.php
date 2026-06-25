<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Base model used as the dataClass of the GridField list when exercising
 * the ClassName-change branch of saveFormIntoRecord().
 */
class TestModel extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormItemRequestTest_TestModel';

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
