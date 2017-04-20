<?php

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Class3 extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Class3';

    private static $db = array(
        'text' => 'Varchar'
    );

    private static $belongs_many_many = array(
        'ones' => Class1::class
    );
}
