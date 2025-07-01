<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class TestGeneratedColumnsManipulationExtension extends Extension implements TestOnly
{
    /**
     * @internal
     */
    private static string $updateColumnName;

    public static function setUpdateColumn($columnName): void
    {
        TestGeneratedColumnsManipulationExtension::$updateColumnName = $columnName;
    }

    protected function augmentWrite(array &$manipulation): void
    {
        $manipulation['DataObjectTest_TestGeneratedColumns']['fields'][TestGeneratedColumnsManipulationExtension::$updateColumnName] = 'blah';
    }
}
