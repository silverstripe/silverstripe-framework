<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests\GridField\GridFieldConfigTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField_URLHandler;

class MyOtherComponent implements GridField_URLHandler, TestOnly
{
    public function getURLHandlers($gridField)
    {
        return array();
    }
}
