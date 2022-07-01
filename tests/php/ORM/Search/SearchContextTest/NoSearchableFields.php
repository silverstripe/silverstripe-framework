<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class NoSearchableFields extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_NoSearchableFields';

    private static $db = [
        'Name' => 'Varchar',
        'Email' => 'Varchar',
        'HairColor' => 'Varchar',
        'EyeColor' => 'Varchar'
    ];

    private static $has_one = [
        'Customer' => Customer::class,
    ];

    private static $summary_fields = [
        'Name' => 'Custom Label',
        'Customer.FirstName' => 'Customer',
        'HairColor',
        'EyeColor',
    ];
}
