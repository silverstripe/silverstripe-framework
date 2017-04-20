<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Play extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Play';
}
