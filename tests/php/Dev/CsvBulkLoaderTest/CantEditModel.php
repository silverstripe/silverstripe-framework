<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\TestOnly;

class CantEditModel extends CanModifyModel implements TestOnly
{
    public function canEdit($member = null)
    {
        return false;
    }
}
