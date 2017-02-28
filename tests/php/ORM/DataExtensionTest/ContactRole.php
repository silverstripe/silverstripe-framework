<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class ContactRole extends DataExtension implements TestOnly
{
    private static $db = array(
        'Website' => 'Varchar',
        'Phone' => 'Varchar(255)',
    );

    private static $has_many = array(
        'RelatedObjects' => RelatedObject::class
    );

    private static $defaults = array(
        'Phone' => '123'
    );

    private static $api_access = true;
}
