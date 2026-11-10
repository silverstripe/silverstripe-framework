<?php

namespace SilverStripe\Dev\Tests\Command\UnloadableDataClassTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class LoadableThing extends DataObject implements TestOnly
{
    private static string $table_name = 'UnloadableDataClassTest_LoadableThing';

    public static array $calls = [];

    public function requireDefaultRecords()
    {
        static::$calls[] = 'requireDefaultRecords';
        parent::requireDefaultRecords();
    }

    public function onAfterBuild()
    {
        static::$calls[] = 'onAfterBuild';
        parent::onAfterBuild();
    }
}
