<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class SortedObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaGenerationTest_SortedObject';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int',
        'SomeDBText' => 'Text',
        'SomeDBHtmlText' => 'HTMLText',
        'SomeDBHtmlVarchar' => 'HTMLVarchar',
    ];

    private static $default_sort = 'Sort';
}
