<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestGeneratedColumns extends DataObject implements TestOnly
{
    private static string $table_name = 'DataObjectTest_TestGeneratedColumns';

    private static array $db = [
        'BaseField' => 'Varchar(255)',
        'GeneratedField1' => 'Generated("Varchar(255)", "CONCAT(\\"BaseField\\", \'_etc\')", "VIRTUAL")',
        'GeneratedField2' => 'Generated("Varchar(255)", "CONCAT(\\"BaseField\\", \'_etc\')", "STORED")',
    ];
}
