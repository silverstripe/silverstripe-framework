<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class ContactRole extends DataExtension implements TestOnly
{
    private static $db = [
        'Website' => 'Varchar',
        'Phone' => 'Varchar(255)',
    ];

    private static $has_many = [
        'RelatedObjects' => RelatedObject::class
    ];

    private static $defaults = [
        'Phone' => '123'
    ];

    private static $api_access = true;
}
