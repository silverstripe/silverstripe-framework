<?php

namespace SilverStripe\ORM\Tests\Search\TraversingSearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Competency extends DataObject implements TestOnly
{
    private static $table_name = 'TraversingSearchContextTest_Competency';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $belongs_many_many = array(
        'Teachers' => Teacher::class,
    );
}
