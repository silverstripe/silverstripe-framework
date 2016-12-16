<?php

namespace SilverStripe\ORM\Tests\DBClassNameTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class OtherClass extends DataObject implements TestOnly
{
    private static $table_name = 'DBClassNameTest_OtherClass';

    private static $db = array(
        'Title' => 'Varchar'
    );
}
