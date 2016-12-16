<?php

namespace SilverStripe\Forms\Tests\FormFactoryTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class TestObject extends DataObject
{
    private static $table_name = 'FormFactoryTest_TestObject';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $extensions = [
        Versioned::class,
    ];
}
