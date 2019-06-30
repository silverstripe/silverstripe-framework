<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\FieldType;

use SilverStripe\ORM\DataObject;

class DBEnumTestObject extends DataObject
{

    private static $table_name = 'FieldType_DBEnumTestObject';

    private static $db = [
        'Colour' => 'Enum("Red,Blue,Green")',
    ];
}
