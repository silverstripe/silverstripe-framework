<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

class WithCustomTable extends NoFields
{
    private static $table_name = 'CITWithCustomTable';
    private static $db = array(
        'Description' => 'Text'
    );
}
