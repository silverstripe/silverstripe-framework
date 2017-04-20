<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Person extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Person';

    private static $db = array(
        'Name' => 'Varchar',
        'Email' => 'Varchar',
        'HairColor' => 'Varchar',
        'EyeColor' => 'Varchar'
    );

    private static $searchable_fields = array(
        'Name',
        'HairColor',
        'EyeColor'
    );
}
