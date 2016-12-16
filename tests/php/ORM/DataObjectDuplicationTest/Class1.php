<?php

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Class1 extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Class1';

    private static $db = array(
        'text' => 'Varchar'
    );

    private static $has_many = array(
        'twos' => Class2::class
    );

    private static $many_many = array(
        'threes' => Class3::class
    );
}
