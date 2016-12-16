<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldConfigTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField_URLHandler;

class MyComponent implements GridField_URLHandler, TestOnly
{
    public function getURLHandlers($gridField)
    {
        return array();
    }
}
