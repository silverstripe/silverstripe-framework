<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CanModifyModel extends DataObject implements TestOnly
{
    private static $table_name = 'CsvBulkLoaderTest_CanModifyModel';

    private static array $db = [
        'Title' => 'Varchar',
        'AnotherField' => 'Varchar',
    ];

    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return true;
    }

    public function canDelete($member = null)
    {
        return true;
    }
}
