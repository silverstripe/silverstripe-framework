<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaGenerationTest;

use SilverStripe\Dev\TestOnly;

class TestIndexObject extends TestObject implements TestOnly
{
    private static $table_name = 'DataObjectSchemaGenerationTest_IndexDO';
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Content' => 'Text'
    );

    private static $indexes = array(
        'NameIndex' => 'unique ("Title")',
        'SearchFields' => array(
            'type' => 'fulltext',
            'name' => 'SearchFields',
            'value' => '"Title","Content"'
        )
    );

    /**
 * @config
*/
    private static $indexes_alt = array(
        'NameIndex' => array(
            'type' => 'unique',
            'name' => 'NameIndex',
            'value' => '"Title"'
        ),
        'SearchFields' => 'fulltext ("Title","Content")'
    );
}
