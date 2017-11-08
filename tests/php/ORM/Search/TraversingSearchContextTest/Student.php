<?php

namespace SilverStripe\ORM\Tests\Search\TraversingSearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Filters\PartialMatchFilter;

class Student extends DataObject implements TestOnly
{
    private static $table_name = 'TraversingSearchContextTest_Student';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $many_many = array(
        'Teachers' => Teacher::class,
    );

    private static $searchable_fields = [
        'Name' => [
            'filter' => PartialMatchFilter::class,
            'field' => TextField::class,
            'title' => 'Name',
        ],
        'Teachers.Name' => [
            'filter' => PartialMatchFilter::class,
            'field' => TextField::class,
            'title' => 'Teacher',
        ],
        'Teachers.Competencies.Name' => [
            'filter' => PartialMatchFilter::class,
            'field' => TextField::class,
            'title' => 'Competency',
        ],
    ];
}
