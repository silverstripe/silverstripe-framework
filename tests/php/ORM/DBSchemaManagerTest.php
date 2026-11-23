<?php

namespace SilverStripe\ORM\Tests;

use ReflectionMethod;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\DBSchemaManager;
use SilverStripe\ORM\Tests\DBSchemaManagerTest\ChildClass;
use SilverStripe\ORM\Tests\DBSchemaManagerTest\CountingDBSchemaManager;
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
        $result = $reflectionCanCheck->invoke($manager, $tableName);

        $this->assertSame($expected, $result);
    }

    public function testSchemaListsAreReadOncePerTableDuringSchemaUpdate(): void
    {
        $manager = new CountingDBSchemaManager(['TableA', 'TableB']);

        $manager->schemaUpdate(function () use ($manager) {
            for ($i = 0; $i < 3; $i++) {
                $this->assertTrue($manager->hasField('TableA', 'Title'));
                $this->assertFalse($manager->hasField('TableB', 'Missing'));
                $this->assertFalse($manager->hasField('MissingTable', 'Title'));
                $manager->dontRequireIndex('TableA', 'SomeIndex');
                $manager->fixTableCase('TableA');
            }
        });

        $this->assertSame(1, $manager->getReadCount('fieldList', 'TableA'));
        $this->assertSame(1, $manager->getReadCount('fieldList', 'TableB'));
        $this->assertSame(0, $manager->getReadCount('fieldList', 'MissingTable'));
        $this->assertSame(1, $manager->getReadCount('hasTable', 'TableA'));
        $this->assertSame(1, $manager->getReadCount('hasTable', 'MissingTable'));
        $this->assertSame(1, $manager->getReadCount('indexList', 'TableA'));
        // One read to start the update, one shared by every fixTableCase() call
        $this->assertSame(2, $manager->getReadCount('tableList'));
    }

    public function testSchemaListsAreReadEveryTimeOutsideSchemaUpdate(): void
    {
        $manager = new CountingDBSchemaManager(['TableA']);

        for ($i = 0; $i < 3; $i++) {
            $manager->hasField('TableA', 'Title');
            $manager->fixTableCase('TableA');
        }

        $this->assertSame(3, $manager->getReadCount('fieldList', 'TableA'));
        $this->assertSame(3, $manager->getReadCount('hasTable', 'TableA'));
        $this->assertSame(3, $manager->getReadCount('tableList'));
    }

    public function testSchemaListsAreReadEveryTimeAfterSchemaUpdateIsCancelled(): void
    {
        $manager = new CountingDBSchemaManager(['TableA']);

        $manager->schemaUpdate(function () use ($manager) {
            $manager->hasField('TableA', 'Title');
            $manager->cancelSchemaUpdate();
            $manager->hasField('TableA', 'Title');
            $manager->hasField('TableA', 'Title');
        });

        $this->assertSame(3, $manager->getReadCount('fieldList', 'TableA'));
    }

    public function testSchemaListsAreReadAgainInTheNextSchemaUpdate(): void
    {
        $manager = new CountingDBSchemaManager(['TableA']);
        $readFields = function () use ($manager) {
            $manager->hasField('TableA', 'Title');
            $manager->hasField('TableA', 'Title');
        };

        $manager->schemaUpdate($readFields);
        $manager->schemaUpdate($readFields);

        $this->assertSame(2, $manager->getReadCount('fieldList', 'TableA'));
    }

    public function testRenamingAFieldDuringSchemaUpdateReadsTheFieldListAgain(): void
    {
        $manager = new CountingDBSchemaManager(['TableA']);

        $manager->schemaUpdate(function () use ($manager) {
            $manager->dontRequireField('TableA', 'Title');
            $manager->hasField('TableA', 'Title');
        });

        $this->assertSame(2, $manager->getReadCount('fieldList', 'TableA'));
    }

    public static function provideClearingSchemaLists(): array
    {
        return [
            'clearSchemaMetadata() clears every list' => [
                'clear' => fn (DBSchemaManager $manager) => $manager->clearSchemaMetadata(),
                'expectedReads' => ['fieldList' => [2, 2], 'indexList' => [2, 2], 'hasTable' => [2, 2]],
            ],
            'clearCachedFieldlist() clears the field lists of every table' => [
                'clear' => fn (DBSchemaManager $manager) => $manager->clearCachedFieldlist(),
                'expectedReads' => ['fieldList' => [2, 2], 'indexList' => [1, 1], 'hasTable' => [1, 1]],
            ],
            'clearCachedFieldlist($table) clears only that table\'s field list' => [
                'clear' => fn (DBSchemaManager $manager) => $manager->clearCachedFieldlist('TableA'),
                'expectedReads' => ['fieldList' => [2, 1], 'indexList' => [1, 1], 'hasTable' => [1, 1]],
            ],
        ];
    }

    /**
     * @param array<string, int[]> $expectedReads Read counts per method for TableA and TableB
     */
    #[DataProvider('provideClearingSchemaLists')]
    public function testClearingSchemaListsDuringSchemaUpdate(callable $clear, array $expectedReads): void
    {
        $manager = new CountingDBSchemaManager(['TableA', 'TableB']);
        $readLists = function () use ($manager) {
            foreach (['TableA', 'TableB'] as $table) {
                $manager->hasField($table, 'Title');
                $manager->dontRequireIndex($table, 'SomeIndex');
            }
        };

        $manager->schemaUpdate(function () use ($manager, $readLists, $clear) {
            $readLists();
            $clear($manager);
            $readLists();
        });

        foreach ($expectedReads as $method => [$tableAReads, $tableBReads]) {
            $this->assertSame($tableAReads, $manager->getReadCount($method, 'TableA'), "$method on TableA");
            $this->assertSame($tableBReads, $manager->getReadCount($method, 'TableB'), "$method on TableB");
        }
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
