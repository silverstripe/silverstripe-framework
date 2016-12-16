<?php

namespace SilverStripe\Assets\Tests\FileTest;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;

class MyCustomFile extends File implements TestOnly
{
    private static $table_name = 'FileTest_MyCustomFile';
}
