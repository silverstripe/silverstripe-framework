<?php

namespace SilverStripe\ORM\Tests\DBClassNameTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CustomDefault extends DataObject implements TestOnly
{
    private static $table_name = 'DBClassNameTest_CustomDefault';

    private static $default_classname = CustomDefaultSubclass::class;

    private static $db = array(
        'Title' => 'Varchar'
    );
}
