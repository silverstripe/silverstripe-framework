<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class HasComposites extends DataObject implements TestOnly
{
    private static $db = [
        'Amount' => 'Money'
    ];

    private static $has_one = [
        'RegularHasOne' => ChildClass::class,
        'Polymorpheus' => DataObject::class
    ];
}
