<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class SortedObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaGenerationTest_SortedObject';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int',
    ];

    private static $default_sort = 'Sort';
}
