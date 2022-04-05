<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filters\EndsWithFilter;

class Stadium extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldTest_Stadium';

    private static $db = [
        'Name' => 'Varchar',
        'City' => 'Varchar',
        'Country' => 'Varchar',
        'Type' => 'Varchar'
    ];

    private static $searchable_fields = [
        'Name',
        'City' => [
            'filter' => EndsWithFilter::class
        ],
        'Country' => [
            'filter' => 'ExactMatchFilter'
        ],
    ];

    private static $extensions = [
        StadiumExtension::class,
    ];
}
