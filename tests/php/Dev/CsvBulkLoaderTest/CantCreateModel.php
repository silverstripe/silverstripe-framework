<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\TestOnly;

class CantCreateModel extends CanModifyModel implements TestOnly
{
    public function canCreate($member = null, $context = [])
    {
        return false;
    }
}
