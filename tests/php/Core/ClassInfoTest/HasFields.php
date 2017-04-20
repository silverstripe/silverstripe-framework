<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

class HasFields extends NoFields
{
    private static $table_name = 'ClassInfoTest_HasFields';

    private static $db = array(
        'Description' => 'Varchar'
    );
}
