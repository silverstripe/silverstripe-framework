<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

class WithCustomTable extends NoFields
{
    private static $table_name = 'DOSTWithCustomTable';
    private static $db = array(
        'Description' => 'Text'
    );
}
