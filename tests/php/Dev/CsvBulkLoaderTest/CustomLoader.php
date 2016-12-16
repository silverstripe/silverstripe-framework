<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\Dev\TestOnly;

class CustomLoader extends CsvBulkLoader implements TestOnly
{
    public function importFirstName(&$obj, $val, $record)
    {
        $obj->FirstName = "Customized {$val}";
    }

    public function updatePlayer(&$obj, $val, $record)
    {
        $obj->FirstName .= $val . '. ';
    }
}
