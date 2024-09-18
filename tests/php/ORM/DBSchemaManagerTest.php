<?php

namespace SilverStripe\ORM\Tests;

use ReflectionMethod;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\DBSchemaManager;
use SilverStripe\ORM\Tests\DBSchemaManagerTest\ChildClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\ORM\Tests\DBSchemaManagerTest\TestDBSchemaManager;

class DBSchemaManagerTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideCanCheckAndRepairTable()
    {
        return [
            // not ignored, but globally not allowed
            [
                'tableName' => 'DBSchemaManagerTest_SomeModel',
                'checkAndRepairOnBuild' => false,
                'expected' => false,
            ],
            // allowed because it's not in the ignore list
            [
                'tableName' => 'DBSchemaManagerTest_SomeModel',
                'checkAndRepairOnBuild' => true,
                'expected' => true,
            ],
            // not allowed because it's the base class for an ignored class
            [
                'tableName' => 'DBSchemaManagerTest_BaseClass',
                'checkAndRepairOnBuild' => true,
                'expected' => false,
            ],
            // not allowed because it's explicitly in the ignore list
            [
                'tableName' => 'DBSchemaManagerTest_ChildClass',
                'checkAndRepairOnBuild' => true,
                'expected' => false,
            ],
            // not allowed because it's a subclass of an ignored class
            [
                'tableName' => 'DBSchemaManagerTest_GrandChildClass',
                'checkAndRepairOnBuild' => true,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideCanCheckAndRepairTable')]
    public function testCanCheckAndRepairTable(string $tableName, bool $checkAndRepairOnBuild, bool $expected)
    {
        // set config
        Config::modify()->set(DBSchemaManager::class, 'check_and_repair_on_build', $checkAndRepairOnBuild);
        Config::modify()->set(DBSchemaManager::class, 'exclude_models_from_db_checks', [ChildClass::class]);

        $manager = $this->getConcreteSchemaManager();
        $reflectionCanCheck = new ReflectionMethod($manager, 'canCheckAndRepairTable');
        $reflectionCanCheck->setAccessible(true);
        $result = $reflectionCanCheck->invoke($manager, $tableName);

        $this->assertSame($expected, $result);
    }

    /**
     * DBSchemaManager is an abstract class - this gives us an instance of a concrete subclass.
     * This allows us to test the original abstract implementations of methods without risking accidentally
     * testing overridden methods on something like MySQLSchemaManager
     */
    private function getConcreteSchemaManager(): DBSchemaManager
    {
        return new TestDBSchemaManager();
    }
}
