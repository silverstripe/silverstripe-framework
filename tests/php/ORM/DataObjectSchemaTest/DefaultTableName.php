<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DefaultTableName extends DataObject implements TestOnly
{
    private static $db = [
        'Title' => 'Varchar',
    ];
}
