<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class BaseClass extends DataObject implements TestOnly
{
    private static $table_name = 'ClassInfoTest_BaseClass';
}
