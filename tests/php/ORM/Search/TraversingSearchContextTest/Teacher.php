<?php

namespace SilverStripe\ORM\Tests\Search\TraversingSearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Teacher extends DataObject implements TestOnly
{
    private static $table_name = 'TraversingSearchContextTest_Teacher';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $belongs_many_many = array(
        'Students' => Student::class,
    );

    private static $many_many = array(
        'Competencies' => Competency::class,
    );
}
