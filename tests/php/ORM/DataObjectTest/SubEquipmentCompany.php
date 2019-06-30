<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;

class SubEquipmentCompany extends EquipmentCompany implements TestOnly
{
    private static $table_name = 'DataObjectTest_SubEquipmentCompany';

    private static $db = [
        'SubclassDatabaseField' => 'Varchar',
    ];
}
