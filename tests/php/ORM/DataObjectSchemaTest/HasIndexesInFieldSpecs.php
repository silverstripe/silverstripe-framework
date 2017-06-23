<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class HasIndexesInFieldSpecs extends DataObject implements TestOnly
{
    private static $db = [
        'Normal' => 'Varchar',
        'IndexedTitle' => 'Varchar(255, ["indexed" => true])',
        'NormalMoney' => 'Money',
        'IndexedMoney' => 'Money(["indexed" => true])'
    ];
}
