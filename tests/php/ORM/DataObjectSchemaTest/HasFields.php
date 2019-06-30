<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

class HasFields extends NoFields
{
    private static $table_name = 'DataObjectSchemaTest_HasFields';

    private static $db = array(
        'Description' => 'Varchar',
        'MoneyField' => 'Money',
    );
}
