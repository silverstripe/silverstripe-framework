<?php

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Class2 extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Class2';

    private static $db = array(
        'text' => 'Varchar'
    );

    private static $has_one = array(
        'one' => Class1::class
    );
}
