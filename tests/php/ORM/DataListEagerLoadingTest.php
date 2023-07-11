<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\EagerLoadedList;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\EagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\HasOneEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\HasOneSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\HasOneSubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\BelongsToEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\BelongsToSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\BelongsToSubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\HasManyEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\HasManySubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\HasManySubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManyEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManySubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManySubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManyThroughEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManyThroughSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManyThroughSubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\EagerLoadObjectManyManyThroughEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManyThroughEagerLoadObjectManyManyThroughSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\ManyManyThroughSubEagerLoadObjectManyManyThroughSubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\BelongsManyManyEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\BelongsManyManySubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\BelongsManyManySubSubEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\MixedHasManyEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\MixedHasOneEagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\MixedManyManyEagerLoadObject;

class DataListEagerLoadingTest extends SapphireTest
{

    protected $usesDatabase = true;

    public static function getExtraDataObjects()
    {
        return [
            EagerLoadObject::class,
            HasOneEagerLoadObject::class,
            HasOneSubEagerLoadObject::class,
            HasOneSubSubEagerLoadObject::class,
            BelongsToEagerLoadObject::class,
            BelongsToSubEagerLoadObject::class,
            BelongsToSubSubEagerLoadObject::class,
            HasManyEagerLoadObject::class,
            HasManySubEagerLoadObject::class,
            HasManySubSubEagerLoadObject::class,
            ManyManyEagerLoadObject::class,
            ManyManySubEagerLoadObject::class,
            ManyManySubSubEagerLoadObject::class,
            ManyManyThroughEagerLoadObject::class,
            ManyManyThroughSubEagerLoadObject::class,
            ManyManyThroughSubSubEagerLoadObject::class,
            EagerLoadObjectManyManyThroughEagerLoadObject::class,
            ManyManyThroughEagerLoadObjectManyManyThroughSubEagerLoadObject::class,
            ManyManyThroughSubEagerLoadObjectManyManyThroughSubSubEagerLoadObject::class,
            BelongsManyManyEagerLoadObject::class,
            BelongsManyManySubEagerLoadObject::class,
            BelongsManyManySubSubEagerLoadObject::class,
            MixedHasManyEagerLoadObject::class,
            MixedHasOneEagerLoadObject::class,
            MixedManyManyEagerLoadObject::class,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Set non-zero auto increment offset for each object type so we don't end up with the same IDs across
        // the board. If all of the IDs are 0, 1, 2 then we have no way of knowing if we're accidentally mixing
        // up relation ID lists between different relation lists for different classes.
        $schema = DataObject::getSchema();
        /** @var DataObject $class */
        $numManyManyTables = 0;
        foreach (static::getExtraDataObjects() as $i => $class) {
            $autoIncrementStart = ($i + 1) * 100;
            $table = $schema->baseDataTable($class);
            DB::query("ALTER TABLE $table AUTO_INCREMENT = $autoIncrementStart;");

            // Also adjust auto increment for join tables for the same reason.
            foreach (array_keys($class::config()->get('many_many') ?? []) as $relationName) {
                $manyManyComponent = $schema->manyManyComponent($class, $relationName);

                $joinTable = $manyManyComponent['join'];
                if (is_a($manyManyComponent['relationClass'], ManyManyThroughList::class, true)) {
                    $joinTable = $schema->baseDataTable($joinTable);
                }
                $joinAutoIncrementStart = ($numManyManyTables + 1) * 100 + 50;
                DB::query("ALTER TABLE $joinTable AUTO_INCREMENT = $joinAutoIncrementStart;");
                $numManyManyTables++;
            }
        }
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadRelations(string $iden, array $eagerLoad, int $expected): void
    {
        $this->createEagerLoadData();

        $dataList = EagerLoadObject::get()->eagerLoad(...$eagerLoad);
        list($results, $selectCount) = $this->iterateEagerLoadData($dataList);
        $expectedResults = $this->expectedEagerLoadRelations();
        // Sort because the order of the results doesn't really matter - and has proven to be different in postgres
        sort($expectedResults);
        sort($results);

        $this->assertSame($expectedResults, $results);
        $this->assertSame($expected, $selectCount);
    }

    public function provideEagerLoadRelations(): array
    {
        return [
            // Include the lazy-loaded expectation here, since if the number
            // of queries changes for this we should expect the number
            // to change for eager-loading as well.
            [
                'iden' => 'lazy-load',
                'eagerLoad' => [],
                'expected' => 83
            ],
            [
                'iden' => 'has-one-a',
                'eagerLoad' => [
                    'HasOneEagerLoadObject',
                ],
                'expected' => 82
            ],
            [
                'iden' => 'has-one-b',
                'eagerLoad' => [
                    'HasOneEagerLoadObject.HasOneSubEagerLoadObject',
                ],
                'expected' => 81
            ],
            [
                'iden' => 'has-one-c',
                'eagerLoad' => [
                    'HasOneEagerLoadObject.HasOneSubEagerLoadObject.HasOneSubSubEagerLoadObject',
                ],
                'expected' => 80
            ],
            [
                'iden' => 'belongs-to-a',
                'eagerLoad' => [
                    'BelongsToEagerLoadObject',
                ],
                'expected' => 82
            ],
            [
                'iden' => 'belongs-to-b',
                'eagerLoad' => [
                    'BelongsToEagerLoadObject.BelongsToSubEagerLoadObject',
                ],
                'expected' => 81
            ],
            [
                'iden' => 'belongs-to-c',
                'eagerLoad' => [
                    'BelongsToEagerLoadObject.BelongsToSubEagerLoadObject.BelongsToSubSubEagerLoadObject',
                ],
                'expected' => 80
            ],
            [
                'iden' => 'has-many-a',
                'eagerLoad' => [
                    'HasManyEagerLoadObjects',
                ],
                'expected' => 82
            ],
            [
                'iden' => 'has-many-b',
                'eagerLoad' => [
                    'HasManyEagerLoadObjects.HasManySubEagerLoadObjects',
                ],
                'expected' => 79
            ],
            [
                'iden' => 'has-many-c',
                'eagerLoad' => [
                    'HasManyEagerLoadObjects.HasManySubEagerLoadObjects.HasManySubSubEagerLoadObjects',
                ],
                'expected' => 72
            ],
            [
                'iden' => 'many-many-a',
                'eagerLoad' => [
                    'ManyManyEagerLoadObjects',
                ],
                'expected' => 83 // same number as lazy-load, though without an INNER JOIN
            ],
            [
                'iden' => 'many-many-b',
                'eagerLoad' => [
                    'ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects',
                ],
                'expected' => 81
            ],
            [
                'iden' => 'many-many-c',
                'eagerLoad' => [
                    'ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects.ManyManySubSubEagerLoadObjects',
                ],
                'expected' => 75
            ],
            [
                'iden' => 'many-many-through-a',
                'eagerLoad' => [
                    'ManyManyThroughEagerLoadObjects',
                ],
                'expected' => 83
            ],
            [
                'iden' => 'many-many-through-b',
                'eagerLoad' => [
                    'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects',
                ],
                'expected' => 81
            ],
            [
                'iden' => 'many-many-through-c',
                'eagerLoad' => [
                    'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects.ManyManyThroughSubSubEagerLoadObjects',
                ],
                'expected' => 75
            ],
            [
                'iden' => 'belongs-many-many-a',
                'eagerLoad' => [
                    'BelongsManyManyEagerLoadObjects',
                ],
                'expected' => 83
            ],
            [
                'iden' => 'belongs-many-many-b',
                'eagerLoad' => [
                    'BelongsManyManyEagerLoadObjects.BelongsManyManySubEagerLoadObjects',
                ],
                'expected' => 81
            ],
            [
                'iden' => 'belongs-many-many-c',
                'eagerLoad' => [
                    'BelongsManyManyEagerLoadObjects.BelongsManyManySubEagerLoadObjects.BelongsManyManySubSubEagerLoadObjects',
                ],
                'expected' => 75
            ],
            [
                'iden' => 'mixed-a',
                'eagerLoad' => [
                    'MixedManyManyEagerLoadObjects',
                ],
                'expected' => 83
            ],
            [
                'iden' => 'mixed-b',
                'eagerLoad' => [
                    'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects',
                ],
                'expected' => 80
            ],
            [
                'iden' => 'mixed-c',
                'eagerLoad' => [
                    'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects.MixedHasOneEagerLoadObject',
                ],
                'expected' => 73
            ],
            [
                'iden' => 'duplicates',
                'eagerLoad' => [
                    'MixedManyManyEagerLoadObjects',
                    'MixedManyManyEagerLoadObjects',
                    'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects',
                    'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects',
                    'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects.MixedManyManyEagerLoadObject',
                    'BelongsManyManyEagerLoadObjects.BelongsManyManySubEagerLoadObjects',
                    'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects.MixedHasOneEagerLoadObject',
                ],
                'expected' => 73
            ],
            [
                'iden' => 'all',
                'eagerLoad' => [
                    'HasOneEagerLoadObject.HasOneSubEagerLoadObject.HasOneSubSubEagerLoadObject',
                    'BelongsToEagerLoadObject.BelongsToSubEagerLoadObject.BelongsToSubSubEagerLoadObject',
                    'HasManyEagerLoadObjects.HasManySubEagerLoadObjects.HasManySubSubEagerLoadObjects',
                    'ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects.ManyManySubSubEagerLoadObjects',
                    'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects.ManyManyThroughSubSubEagerLoadObjects',
                    'BelongsManyManyEagerLoadObjects.BelongsManyManySubEagerLoadObjects.BelongsManyManySubSubEagerLoadObjects',
                    'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects.MixedHasOneEagerLoadObject',
                ],
                'expected' => 32
            ],
        ];
    }

    private function expectedEagerLoadRelations(): array
    {
        return [
            'obj 0',
            'hasOneObj 0',
            'hasOneSubObj 0',
            'hasOneSubSubObj 0',
            'belongsToObj 0',
            'belongsToSubObj 0',
            'belongsToSubSubObj 0',
            'hasManyObj 0 0',
            'hasManySubObj 0 0 0',
            'hasManySubSubObj 0 0 0 0',
            'hasManySubSubObj 0 0 0 1',
            'hasManySubObj 0 0 1',
            'hasManySubSubObj 0 0 1 0',
            'hasManySubSubObj 0 0 1 1',
            'hasManyObj 0 1',
            'hasManySubObj 0 1 0',
            'hasManySubSubObj 0 1 0 0',
            'hasManySubSubObj 0 1 0 1',
            'hasManySubObj 0 1 1',
            'hasManySubSubObj 0 1 1 0',
            'hasManySubSubObj 0 1 1 1',
            'manyManyObj 0 0',
            'manyManySubObj 0 0 0',
            'manyManySubSubObj 0 0 0 0',
            'manyManySubSubObj 0 0 0 1',
            'manyManySubObj 0 0 1',
            'manyManySubSubObj 0 0 1 0',
            'manyManySubSubObj 0 0 1 1',
            'manyManyObj 0 1',
            'manyManySubObj 0 1 0',
            'manyManySubSubObj 0 1 0 0',
            'manyManySubSubObj 0 1 0 1',
            'manyManySubObj 0 1 1',
            'manyManySubSubObj 0 1 1 0',
            'manyManySubSubObj 0 1 1 1',
            'manyManyThroughObj 0 0',
            'manyManyThroughSubObj 0 0 0',
            'manyManyThroughSubSubObj 0 0 0 0',
            'manyManyThroughSubSubObj 0 0 0 1',
            'manyManyThroughSubObj 0 0 1',
            'manyManyThroughSubSubObj 0 0 1 0',
            'manyManyThroughSubSubObj 0 0 1 1',
            'manyManyThroughObj 0 1',
            'manyManyThroughSubObj 0 1 0',
            'manyManyThroughSubSubObj 0 1 0 0',
            'manyManyThroughSubSubObj 0 1 0 1',
            'manyManyThroughSubObj 0 1 1',
            'manyManyThroughSubSubObj 0 1 1 0',
            'manyManyThroughSubSubObj 0 1 1 1',
            'belongsManyManyObj 0 0',
            'belongsManyManySubObj 0 0 0',
            'belongsManyManySubSubObj 0 0 0 0',
            'belongsManyManySubSubObj 0 0 0 1',
            'belongsManyManySubObj 0 0 1',
            'belongsManyManySubSubObj 0 0 1 0',
            'belongsManyManySubSubObj 0 0 1 1',
            'belongsManyManyObj 0 1',
            'belongsManyManySubObj 0 1 0',
            'belongsManyManySubSubObj 0 1 0 0',
            'belongsManyManySubSubObj 0 1 0 1',
            'belongsManyManySubObj 0 1 1',
            'belongsManyManySubSubObj 0 1 1 0',
            'belongsManyManySubSubObj 0 1 1 1',
            'mixedManyManyObj 0 0',
            'mixedHasManyObj 0 0 0',
            'mixedHasOneObj 0 0 0 1',
            'mixedHasManyObj 0 0 1',
            'mixedHasOneObj 0 0 1 1',
            'mixedManyManyObj 0 1',
            'mixedHasManyObj 0 1 0',
            'mixedHasOneObj 0 1 0 1',
            'mixedHasManyObj 0 1 1',
            'mixedHasOneObj 0 1 1 1',
            'obj 1',
            'hasOneObj 1',
            'hasOneSubObj 1',
            'hasOneSubSubObj 1',
            'belongsToObj 1',
            'belongsToSubObj 1',
            'belongsToSubSubObj 1',
            'hasManyObj 1 0',
            'hasManySubObj 1 0 0',
            'hasManySubSubObj 1 0 0 0',
            'hasManySubSubObj 1 0 0 1',
            'hasManySubObj 1 0 1',
            'hasManySubSubObj 1 0 1 0',
            'hasManySubSubObj 1 0 1 1',
            'hasManyObj 1 1',
            'hasManySubObj 1 1 0',
            'hasManySubSubObj 1 1 0 0',
            'hasManySubSubObj 1 1 0 1',
            'hasManySubObj 1 1 1',
            'hasManySubSubObj 1 1 1 0',
            'hasManySubSubObj 1 1 1 1',
            'manyManyObj 1 0',
            'manyManySubObj 1 0 0',
            'manyManySubSubObj 1 0 0 0',
            'manyManySubSubObj 1 0 0 1',
            'manyManySubObj 1 0 1',
            'manyManySubSubObj 1 0 1 0',
            'manyManySubSubObj 1 0 1 1',
            'manyManyObj 1 1',
            'manyManySubObj 1 1 0',
            'manyManySubSubObj 1 1 0 0',
            'manyManySubSubObj 1 1 0 1',
            'manyManySubObj 1 1 1',
            'manyManySubSubObj 1 1 1 0',
            'manyManySubSubObj 1 1 1 1',
            'manyManyThroughObj 1 0',
            'manyManyThroughSubObj 1 0 0',
            'manyManyThroughSubSubObj 1 0 0 0',
            'manyManyThroughSubSubObj 1 0 0 1',
            'manyManyThroughSubObj 1 0 1',
            'manyManyThroughSubSubObj 1 0 1 0',
            'manyManyThroughSubSubObj 1 0 1 1',
            'manyManyThroughObj 1 1',
            'manyManyThroughSubObj 1 1 0',
            'manyManyThroughSubSubObj 1 1 0 0',
            'manyManyThroughSubSubObj 1 1 0 1',
            'manyManyThroughSubObj 1 1 1',
            'manyManyThroughSubSubObj 1 1 1 0',
            'manyManyThroughSubSubObj 1 1 1 1',
            'belongsManyManyObj 1 0',
            'belongsManyManySubObj 1 0 0',
            'belongsManyManySubSubObj 1 0 0 0',
            'belongsManyManySubSubObj 1 0 0 1',
            'belongsManyManySubObj 1 0 1',
            'belongsManyManySubSubObj 1 0 1 0',
            'belongsManyManySubSubObj 1 0 1 1',
            'belongsManyManyObj 1 1',
            'belongsManyManySubObj 1 1 0',
            'belongsManyManySubSubObj 1 1 0 0',
            'belongsManyManySubSubObj 1 1 0 1',
            'belongsManyManySubObj 1 1 1',
            'belongsManyManySubSubObj 1 1 1 0',
            'belongsManyManySubSubObj 1 1 1 1',
            'mixedManyManyObj 1 0',
            'mixedHasManyObj 1 0 0',
            'mixedHasOneObj 1 0 0 1',
            'mixedHasManyObj 1 0 1',
            'mixedHasOneObj 1 0 1 1',
            'mixedManyManyObj 1 1',
            'mixedHasManyObj 1 1 0',
            'mixedHasOneObj 1 1 0 1',
            'mixedHasManyObj 1 1 1',
            'mixedHasOneObj 1 1 1 1',
        ];
    }

    private function createEagerLoadData(
        int $numBaseRecords = 2,
        int $numLevel1Records = 2,
        int $numLevel2Records = 2,
        int $numLevel3Records = 2
    ): void {
        for ($i = 0; $i < $numBaseRecords; $i++) {
            // base object
            $obj = new EagerLoadObject();
            $obj->Title = "obj $i";
            $objID = $obj->write();
            // has_one
            $hasOneObj = new HasOneEagerLoadObject();
            $hasOneObj->Title = "hasOneObj $i";
            $hasOneObjID = $hasOneObj->write();
            $obj->HasOneEagerLoadObjectID = $hasOneObjID;
            $obj->write();
            $hasOneSubObj = new HasOneSubEagerLoadObject();
            $hasOneSubObj->Title = "hasOneSubObj $i";
            $hasOneSubObjID = $hasOneSubObj->write();
            $hasOneObj->HasOneSubEagerLoadObjectID = $hasOneSubObjID;
            $hasOneObj->write();
            $hasOneSubSubObj = new HasOneSubSubEagerLoadObject();
            $hasOneSubSubObj->Title = "hasOneSubSubObj $i";
            $hasOneSubSubObjID = $hasOneSubSubObj->write();
            $hasOneSubObj->HasOneSubSubEagerLoadObjectID = $hasOneSubSubObjID;
            $hasOneSubObj->write();
            // belongs_to
            $belongsToObj = new BelongsToEagerLoadObject();
            $belongsToObj->EagerLoadObjectID = $objID;
            $belongsToObj->Title = "belongsToObj $i";
            $belongsToObjID = $belongsToObj->write();
            $belongsToSubObj = new BelongsToSubEagerLoadObject();
            $belongsToSubObj->BelongsToEagerLoadObjectID = $belongsToObjID;
            $belongsToSubObj->Title = "belongsToSubObj $i";
            $belongsToSubObjID = $belongsToSubObj->write();
            $belongsToSubSubObj = new BelongsToSubSubEagerLoadObject();
            $belongsToSubSubObj->BelongsToSubEagerLoadObjectID = $belongsToSubObjID;
            $belongsToSubSubObj->Title = "belongsToSubSubObj $i";
            $belongsToSubSubObj->write();
            // has_many
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $hasManyObj = new HasManyEagerLoadObject();
                $hasManyObj->EagerLoadObjectID = $objID;
                $hasManyObj->Title = "hasManyObj $i $j";
                $hasManyObjID = $hasManyObj->write();
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $hasManySubObj = new HasManySubEagerLoadObject();
                    $hasManySubObj->HasManyEagerLoadObjectID = $hasManyObjID;
                    $hasManySubObj->Title = "hasManySubObj $i $j $k";
                    $hasManySubObjID = $hasManySubObj->write();
                    for ($l = 0; $l < $numLevel3Records; $l++) {
                        $hasManySubSubObj = new HasManySubSubEagerLoadObject();
                        $hasManySubSubObj->HasManySubEagerLoadObjectID = $hasManySubObjID;
                        $hasManySubSubObj->Title = "hasManySubSubObj $i $j $k $l";
                        $hasManySubSubObj->write();
                    }
                }
            }
            // many_many
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $manyManyObj = new ManyManyEagerLoadObject();
                $manyManyObj->Title = "manyManyObj $i $j";
                $manyManyObj->write();
                $obj->ManyManyEagerLoadObjects()->add($manyManyObj);
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $manyManySubObj = new ManyManySubEagerLoadObject();
                    $manyManySubObj->Title = "manyManySubObj $i $j $k";
                    $manyManySubObj->write();
                    $manyManyObj->ManyManySubEagerLoadObjects()->add($manyManySubObj);
                    for ($l = 0; $l < $numLevel3Records; $l++) {
                        $manyManySubSubObj = new ManyManySubSubEagerLoadObject();
                        $manyManySubSubObj->Title = "manyManySubSubObj $i $j $k $l";
                        $manyManySubSubObj->write();
                        $manyManySubObj->ManyManySubSubEagerLoadObjects()->add($manyManySubSubObj);
                    }
                }
            }
            // many_many with extraFields
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $manyManyObj = new ManyManyEagerLoadObject();
                $manyManyObj->Title = "manyManyObj $i $j";
                $manyManyObj->write();
                $obj->ManyManyEagerLoadWithExtraFields()->add($manyManyObj, [
                    'SomeText' => "Some text here $i $j",
                    'SomeBool' => $j % 2 === 0, // true if even
                    'SomeInt' => $j,
                ]);
            }
            // many_many_through
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $manyManyThroughObj = new ManyManyThroughEagerLoadObject();
                $manyManyThroughObj->Title = "manyManyThroughObj $i $j";
                $manyManyThroughObj->write();
                $obj->ManyManyThroughEagerLoadObjects()->add($manyManyThroughObj, [
                    'Title' => "Some text here $i $j",
                    'SomeBool' => $j % 2 === 0, // true if even
                    'SomeInt' => $j,
                ]);
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $manyManyThroughSubObj = new ManyManyThroughSubEagerLoadObject();
                    $manyManyThroughSubObj->Title = "manyManyThroughSubObj $i $j $k";
                    $manyManyThroughSubObj->write();
                    $manyManyThroughObj->ManyManyThroughSubEagerLoadObjects()->add($manyManyThroughSubObj);
                    for ($l = 0; $l < $numLevel3Records; $l++) {
                        $manyManyThroughSubSubObj = new ManyManyThroughSubSubEagerLoadObject();
                        $manyManyThroughSubSubObj->Title = "manyManyThroughSubSubObj $i $j $k $l";
                        $manyManyThroughSubSubObj->write();
                        $manyManyThroughSubObj->ManyManyThroughSubSubEagerLoadObjects()->add($manyManyThroughSubSubObj);
                    }
                }
            }
            // belongs_many_many
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $belongsManyManyObj = new BelongsManyManyEagerLoadObject();
                $belongsManyManyObj->Title = "belongsManyManyObj $i $j";
                $belongsManyManyObj->write();
                $obj->BelongsManyManyEagerLoadObjects()->add($belongsManyManyObj);
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $belongsManyManySubObj = new BelongsManyManySubEagerLoadObject();
                    $belongsManyManySubObj->Title = "belongsManyManySubObj $i $j $k";
                    $belongsManyManySubObj->write();
                    $belongsManyManyObj->BelongsManyManySubEagerLoadObjects()->add($belongsManyManySubObj);
                    for ($l = 0; $l < $numLevel3Records; $l++) {
                        $belongsManyManySubSubObj = new BelongsManyManySubSubEagerLoadObject();
                        $belongsManyManySubSubObj->Title = "belongsManyManySubSubObj $i $j $k $l";
                        $belongsManyManySubSubObj->write();
                        $belongsManyManySubObj->BelongsManyManySubSubEagerLoadObjects()->add($belongsManyManySubSubObj);
                    }
                }
            }
            // mixed
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $mixedManyManyObj = new MixedManyManyEagerLoadObject();
                $mixedManyManyObj->Title = "mixedManyManyObj $i $j";
                $mixedManyManyObj->write();
                $obj->MixedManyManyEagerLoadObjects()->add($mixedManyManyObj);
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $mixedHasManyObj = new MixedHasManyEagerLoadObject();
                    $mixedHasManyObj->Title = "mixedHasManyObj $i $j $k";
                    $mixedHasManyObjID = $mixedHasManyObj->write();
                    $mixedManyManyObj->MixedHasManyEagerLoadObjects()->add($mixedHasManyObj);
                    for ($l = 0; $l < $numLevel3Records; $l++) {
                        $mixedHasOneObj = new MixedHasOneEagerLoadObject();
                        $mixedHasOneObj->Title = "mixedHasOneObj $i $j $k $l";
                        $mixedHasOneObjID = $mixedHasOneObj->write();
                        $mixedHasManyObj->MixedHasOneEagerLoadObjectID = $mixedHasOneObjID;
                        $mixedHasManyObj->write();
                    }
                }
            }
        }
    }

    private function iterateEagerLoadData(DataList $dataList, int $chunks = 0): array
    {
        $results = [];
        $selectCount = -1;
        $showqueries = $_REQUEST['showqueries'] ?? null;
        try {
            // force showqueries on to count the number of SELECT statements via output-buffering
            // if this approach turns out to be too brittle later on, switch to what debugbar
            // does and use tractorcow/proxy-db which should be installed as a dev-dependency
            // https://github.com/lekoala/silverstripe-debugbar/blob/master/code/Collector/DatabaseCollector.php#L79
            $_REQUEST['showqueries'] = 1;
            ob_start();
            echo '__START_ITERATE__';
            $results = [];
            $i = 0;
            if ($chunks) {
                $dataList = $dataList->chunkedFetch($chunks);
            }
            foreach ($dataList as $obj) {
                // base obj
                $results[] = $obj->Title;
                // has_one
                $hasOneObj = $obj->HasOneEagerLoadObject();
                $hasOneSubObj = $hasOneObj->HasOneSubEagerLoadObject();
                $hasOneSubSubObj = $hasOneSubObj->HasOneSubSubEagerLoadObject();
                $results[] = $hasOneObj->Title;
                $results[] = $hasOneSubObj->Title;
                $results[] = $hasOneSubSubObj->Title;
                // belongs_to
                $belongsToObj = $obj->BelongsToEagerLoadObject();
                $belongsToSubObj = $belongsToObj->BelongsToSubEagerLoadObject();
                $belongsToSubSubObj = $belongsToSubObj->BelongsToSubSubEagerLoadObject();
                $results[] = $belongsToObj->Title;
                $results[] = $belongsToSubObj->Title;
                $results[] = $belongsToSubSubObj->Title;
                // has_many
                foreach ($obj->HasManyEagerLoadObjects() as $hasManyObj) {
                    $results[] = $hasManyObj->Title;
                    foreach ($hasManyObj->HasManySubEagerLoadObjects() as $hasManySubObj) {
                        $results[] = $hasManySubObj->Title;
                        foreach ($hasManySubObj->HasManySubSubEagerLoadObjects() as $hasManySubSubObj) {
                            $results[] = $hasManySubSubObj->Title;
                        }
                    }
                }
                // many_many
                foreach ($obj->ManyManyEagerLoadObjects() as $manyManyObj) {
                    $results[] = $manyManyObj->Title;
                    foreach ($manyManyObj->ManyManySubEagerLoadObjects() as $manyManySubObj) {
                        $results[] = $manyManySubObj->Title;
                        foreach ($manyManySubObj->ManyManySubSubEagerLoadObjects() as $manyManySubSubObj) {
                            $results[] = $manyManySubSubObj->Title;
                        }
                    }
                }
                // many_many_through
                foreach ($obj->ManyManyThroughEagerLoadObjects() as $manyManyThroughObj) {
                    $results[] = $manyManyThroughObj->Title;
                    foreach ($manyManyThroughObj->ManyManyThroughSubEagerLoadObjects() as $manyManyThroughSubObj) {
                        $results[] = $manyManyThroughSubObj->Title;
                        foreach ($manyManyThroughSubObj->ManyManyThroughSubSubEagerLoadObjects() as $manyManyThroughSubSubObj) {
                            $results[] = $manyManyThroughSubSubObj->Title;
                        }
                    }
                }
                // belongs_many_many
                foreach ($obj->BelongsManyManyEagerLoadObjects() as $belongsManyManyObj) {
                    $results[] = $belongsManyManyObj->Title;
                    foreach ($belongsManyManyObj->BelongsManyManySubEagerLoadObjects() as $belongsManyManySubObj) {
                        $results[] = $belongsManyManySubObj->Title;
                        foreach ($belongsManyManySubObj->BelongsManyManySubSubEagerLoadObjects() as $belongsManyManySubSubObj) {
                            $results[] = $belongsManyManySubSubObj->Title;
                        }
                    }
                }
                // mixed
                foreach ($obj->MixedManyManyEagerLoadObjects() as $mixedManyManyObj) {
                    $results[] = $mixedManyManyObj->Title;
                    foreach ($mixedManyManyObj->MixedHasManyEagerLoadObjects() as $mixedHasManyObj) {
                        $results[] = $mixedHasManyObj->Title;
                        $results[] = $mixedHasManyObj->MixedHasOneEagerLoadObject()->Title;
                    }
                }
            }
            $s = ob_get_clean();
            $s = preg_replace('/.*__START_ITERATE__/s', '', $s);
            $selectCount = substr_count($s, ': SELECT');
        } finally {
            if ($showqueries) {
                $_REQUEST['showqueries'] = $showqueries;
            } else {
                unset($_REQUEST['showqueries']);
            }
        }
        return [$results, $selectCount];
    }

    public function testEagerLoadFourthLevelException(): void
    {
        $eagerLoadRelation = implode('.', [
            'MixedManyManyEagerLoadObjects',
            'MixedHasManyEagerLoadObjects',
            'MixedHasOneEagerLoadObject',
            'FourthLevel'
        ]);
        $expectedMessage = implode(' - ', [
            'Eager loading only supports up to 3 levels of nesting, passed 4 levels',
            $eagerLoadRelation
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        EagerLoadObject::get()->eagerLoad($eagerLoadRelation);
    }

    /**
     * @dataProvider provideEagerLoadInvalidRelationException
     */
    public function testEagerLoadInvalidRelationException(string $eagerLoadRelation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid relation passed to eagerLoad() - $eagerLoadRelation");
        $this->createEagerLoadData();
        EagerLoadObject::get()->eagerLoad($eagerLoadRelation)->toArray();
    }

    public function provideEagerLoadInvalidRelationException(): array
    {
        return [
            [
                'Invalid',
            ],
            [
                'MixedManyManyEagerLoadObjects.Invalid',
            ],
            [
                'MixedManyManyEagerLoadObjects.MixedHasManyEagerLoadObjects.Invalid'
            ]
        ];
    }

    /**
     * @dataProvider provideEagerLoadManyManyExtraFields
     */
    public function testEagerLoadManyManyExtraFields(string $parentClass, string $eagerLoadRelation): void
    {
        $this->createEagerLoadData();

        foreach (DataObject::get($parentClass)->eagerLoad($eagerLoadRelation) as $parentRecord) {
            if ($parentClass === EagerLoadObject::class) {
                $this->validateEagerLoadManyManyExtraFields($parentRecord->ManyManyEagerLoadWithExtraFields());
            } else {
                foreach ($parentRecord->EagerLoadObjects() as $relationRecord) {
                    $this->validateEagerLoadManyManyExtraFields($relationRecord->ManyManyEagerLoadWithExtraFields());
                }
            }
        }
    }

    private function validateEagerLoadManyManyExtraFields($relationList): void
    {
        foreach ($relationList as $record) {
            preg_match('/manyManyObj (?<i>\d+) (?<j>\d+)/', $record->Title, $matches);
            $i = (int) $matches['i'];
            $j = (int) $matches['j'];
            $this->assertSame("Some text here $i $j", $record->SomeText);
            // Bool fields are just small ints, so the data is either 1 or 0
            $this->assertSame($j % 2 === 0 ? 1 : 0, $record->SomeBool);
            $this->assertSame($j, $record->SomeInt);
        }
    }

    public function provideEagerLoadManyManyExtraFields(): array
    {
        return [
            [
                EagerLoadObject::class,
                'ManyManyEagerLoadWithExtraFields',
            ],
            [
                BelongsManyManyEagerLoadObject::class,
                'EagerLoadObjects.ManyManyEagerLoadWithExtraFields',
            ]
        ];
    }

    /**
     * @dataProvider provideEagerLoadManyManyThroughJoinRecords
     */
    public function testEagerLoadManyManyThroughJoinRecords(string $parentClass, string $eagerLoadRelation): void
    {
        $this->createEagerLoadData();

        foreach (DataObject::get($parentClass)->eagerLoad($eagerLoadRelation) as $parentRecord) {
            if ($parentClass === EagerLoadObject::class) {
                $this->validateEagerLoadManyManyThroughJoinRecords($parentRecord->ManyManyThroughEagerLoadObjects());
            } else {
                foreach ($parentRecord->EagerLoadObjects() as $relationRecord) {
                    $this->validateEagerLoadManyManyThroughJoinRecords($relationRecord->ManyManyThroughEagerLoadObjects());
                }
            }
        }
    }

    private function validateEagerLoadManyManyThroughJoinRecords($relationList): void
    {
        /** @var DataObject $record */
        foreach ($relationList as $record) {
            $joinRecord = $record->getJoin();
            $this->assertNotNull($joinRecord);

            preg_match('/manyManyThroughObj (?<i>\d+) (?<j>\d+)/', $record->Title, $matches);
            $i = (int) $matches['i'];
            $j = (int) $matches['j'];
            $this->assertSame("Some text here $i $j", $joinRecord->Title);
            // Bool fields are just small ints, so the data is either 1 or 0
            $this->assertSame($j % 2 === 0 ? 1 : 0, $joinRecord->SomeBool);
            $this->assertSame($j, $joinRecord->SomeInt);
        }
    }

    public function provideEagerLoadManyManyThroughJoinRecords(): array
    {
        return [
            [
                EagerLoadObject::class,
                'ManyManyThroughEagerLoadObjects',
            ],
            [
                BelongsManyManyEagerLoadObject::class,
                'EagerLoadObjects.ManyManyThroughEagerLoadObjects',
            ]
        ];
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadingFilteredList(string $iden, array $eagerLoad): void
    {
        $this->createEagerLoadData(5);
        $filter = ['Title:GreaterThan' => 'obj 0'];
        $dataList = EagerLoadObject::get()->filter($filter)->eagerLoad(...$eagerLoad);

        // Validate that filtering results still actually works on the base list
        $this->assertListEquals([
            ['Title' => 'obj 1'],
            ['Title' => 'obj 2'],
            ['Title' => 'obj 3'],
            ['Title' => 'obj 4'],
        ], $dataList);

        $this->validateEagerLoadingResults($iden, EagerLoadObject::get()->filter($filter), $dataList);
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadingSortedList(string $iden, array $eagerLoad): void
    {
        $this->createEagerLoadData(3);
        $items = [
            'obj 0',
            'obj 1',
            'obj 2',
        ];

        foreach (['ASC', 'DESC'] as $order) {
            $sort = "Title $order";
            $dataList = EagerLoadObject::get()->sort($sort)->eagerLoad(...$eagerLoad);

            if ($order === 'DESC') {
                $items = array_reverse($items);
            }

            // Validate that sorting results still actually works on the base list
            $this->assertSame($items, $dataList->column('Title'));
        }

        // We don't care about the order after this point, so whichever order we've got will be fine.
        // We just want to validate that the data was correctly eager loaded.
        $this->validateEagerLoadingResults($iden, EagerLoadObject::get()->sort($sort), $dataList);
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadingLimitedList(string $iden, array $eagerLoad): void
    {
        // Make sure to create more base records AND more records on at least one relation than the limit
        // to ensure the limit isn't accidentally carried through to the relations.
        $this->createEagerLoadData(6, numLevel3Records: 6);
        $limit = 5;
        $dataList = EagerLoadObject::get()->limit($limit)->eagerLoad(...$eagerLoad);

        // Validate that limiting results still actually works on the base list
        $this->assertCount($limit, $dataList);

        $this->validateEagerLoadingResults($iden, EagerLoadObject::get()->limit($limit), $dataList);
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testRepeatedIterationOfEagerLoadedList(string $iden, array $eagerLoad): void
    {
        // We need at least 3 base records for many_many relations to have fewer db queries than lazy-loaded lists.
        $this->createEagerLoadData(3);
        $dataList = EagerLoadObject::get()->eagerLoad(...$eagerLoad);

        // Validate twice - each validation requires a full iteration over all records including the base list.
        $this->validateEagerLoadingResults($iden, EagerLoadObject::get(), $dataList);
        $this->validateEagerLoadingResults($iden, EagerLoadObject::get(), $dataList);
    }

    /**
     * This test validates that you can call eagerLoad() anywhere on the list before
     * execution, including before or after sort/limit/filter, etc - and it will
     * work the same way regardless of when it was called.
     *
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadWorksAnywhereBeforeExecution(string $iden, array $eagerLoad): void
    {
        $this->createEagerLoadData(7);
        $filter = ['Title:LessThan' => 'obj 5'];
        $sort = 'Title DESC';
        $limit = 3;

        $lazyList = EagerLoadObject::get()->filter($filter)->sort($sort)->limit($limit);
        $lazyListArray = $lazyList->map()->toArray();
        $eagerList1 = EagerLoadObject::get()->eagerLoad(...$eagerLoad)->filter($filter)->sort($sort)->limit($limit);
        $eagerList2 = EagerLoadObject::get()->filter($filter)->eagerLoad(...$eagerLoad)->sort($sort)->limit($limit);
        $eagerList3 = EagerLoadObject::get()->filter($filter)->sort($sort)->eagerLoad(...$eagerLoad)->limit($limit);
        $eagerList4 = EagerLoadObject::get()->filter($filter)->sort($sort)->limit($limit)->eagerLoad(...$eagerLoad);

        // Validates that this list is set up correctly, and there are 3 records with this combination of filter/sort/limit.
        $this->assertCount(3, $lazyListArray);

        // This will probably be really slow, but the idea is to validate that no matter when we call eagerLoad(),
        // both the underlying DataList results and all of the eagerloaded data is the same.
        $this->assertSame($lazyListArray, $eagerList1->map()->toArray());
        $this->assertSame($lazyListArray, $eagerList2->map()->toArray());
        $this->assertSame($lazyListArray, $eagerList3->map()->toArray());
        $this->assertSame($lazyListArray, $eagerList4->map()->toArray());
        $this->validateEagerLoadingResults($iden, $lazyList, $eagerList1);
        $this->validateEagerLoadingResults($iden, $lazyList, $eagerList2);
        $this->validateEagerLoadingResults($iden, $lazyList, $eagerList3);
        $this->validateEagerLoadingResults($iden, $lazyList, $eagerList4);
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadWithChunkedFetch(string $iden, array $eagerLoad): void
    {
        $this->createEagerLoadData(10);
        $dataList = EagerLoadObject::get()->eagerLoad(...$eagerLoad);

        $this->validateEagerLoadingResults($iden, EagerLoadObject::get(), $dataList, 3);
    }

    private function validateEagerLoadingResults(string $iden, DataList $lazyList, DataList $eagerList, int $chunks = 0): void
    {
        list($results, $eagerCount) = $this->iterateEagerLoadData($eagerList, $chunks);
        // We can rely on the non-eager-loaded data being correct, since it's validated by other unit tests
        list($expectedResults, $lazyCount) = $this->iterateEagerLoadData($lazyList, $chunks);
        // Sort because the order of the results doesn't really matter - and has proven to be different in postgres
        sort($expectedResults);
        sort($results);

        $this->assertSame([], array_diff($expectedResults, $results));
        $this->assertSame([], array_diff($results, $expectedResults));
        $this->assertSame(count($expectedResults), count($results));

        // Validate that we have the same eager-loaded data as the lazy-loaded list, and that lazy-loaded lists
        // execute less database queries than lazy-loaded ones
        $this->assertSame($expectedResults, $results);
        if ($iden !== 'lazy-load') {
            $this->assertLessThan($lazyCount, $eagerCount);
        }
    }

    /**
     * @dataProvider provideEagerLoadingEmptyRelationLists
     */
    public function testEagerLoadingEmptyRelationLists(string $iden, string $eagerLoad): void
    {
        $numBaseRecords = 2;
        $numLevel1Records = 2;
        $numLevel2Records = 2;
        // Similar to createEagerLoadData(), except with less relations and
        // making sure only the first record has any items in its relation lists.
        for ($i = 0; $i < $numBaseRecords; $i++) {
            // base object
            $obj = new EagerLoadObject();
            $obj->Title = "obj $i";
            $objID = $obj->write();
            if ($i > 0) {
                continue;
            }
            // has_many
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $hasManyObj = new HasManyEagerLoadObject();
                $hasManyObj->EagerLoadObjectID = $objID;
                $hasManyObj->Title = "hasManyObj $i $j";
                $hasManyObjID = $hasManyObj->write();
                if ($j > 0) {
                    continue;
                }
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $hasManySubObj = new HasManySubEagerLoadObject();
                    $hasManySubObj->HasManyEagerLoadObjectID = $hasManyObjID;
                    $hasManySubObj->Title = "hasManySubObj $i $j $k";
                    $hasManySubObj->write();
                }
            }
            // many_many
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $manyManyObj = new ManyManyEagerLoadObject();
                $manyManyObj->Title = "manyManyObj $i $j";
                $manyManyObj->write();
                $obj->ManyManyEagerLoadObjects()->add($manyManyObj);
                if ($j > 0) {
                    continue;
                }
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $manyManySubObj = new ManyManySubEagerLoadObject();
                    $manyManySubObj->Title = "manyManySubObj $i $j $k";
                    $manyManySubObj->write();
                    $manyManyObj->ManyManySubEagerLoadObjects()->add($manyManySubObj);
                }
            }
            // many_many_through
            for ($j = 0; $j < $numLevel1Records; $j++) {
                $manyManyThroughObj = new ManyManyThroughEagerLoadObject();
                $manyManyThroughObj->Title = "manyManyThroughObj $i $j";
                $manyManyThroughObj->write();
                $obj->ManyManyThroughEagerLoadObjects()->add($manyManyThroughObj, [
                    'Title' => "Some text here $i $j",
                    'SomeBool' => $j % 2 === 0, // true if even
                    'SomeInt' => $j,
                ]);
                if ($j > 0) {
                    continue;
                }
                for ($k = 0; $k < $numLevel2Records; $k++) {
                    $manyManyThroughSubObj = new ManyManyThroughSubEagerLoadObject();
                    $manyManyThroughSubObj->Title = "manyManyThroughSubObj $i $j $k";
                    $manyManyThroughSubObj->write();
                    $manyManyThroughObj->ManyManyThroughSubEagerLoadObjects()->add($manyManyThroughSubObj);
                }
            }
        }

        $i = 0;
        $relations = explode('.', $eagerLoad);
        foreach (EagerLoadObject::get()->eagerLoad($eagerLoad) as $parentRecord) {
            $relation = $relations[0];
            $list = $parentRecord->$relation();
            // For any record after the first one, there should be nothing in the related list.
            $this->assertCount($i > 0 ? 0 : 2, $list);
            $i++;

            if (count($relations) > 1) {
                $j = 0;
                foreach ($list as $relatedRecord) {
                    $relation = $relations[1];
                    $list2 = $relatedRecord->$relation();
                    // For any record after the first one, there should be nothing in the related list.
                    $this->assertCount($j > 0 ? 0 : 2, $list2);
                    $j++;
                }
            }
        }
    }

    public function provideEagerLoadingEmptyRelationLists(): array
    {
        return [
            [
                'hasmany-onelevel',
                'HasManyEagerLoadObjects',
            ],
            [
                'hasmany-twolevels',
                'HasManyEagerLoadObjects.HasManySubEagerLoadObjects',
            ],
            [
                'manymany-onelevel',
                'ManyManyEagerLoadObjects',
            ],
            [
                'manymany-twolevels',
                'ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects',
            ],
            [
                'manymany-through-onelevel',
                'ManyManyThroughEagerLoadObjects',
            ],
            [
                'manymany-through-twolevels',
                'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects',
            ],
        ];
    }

    public function testFirstHasEagerloadedRelation()
    {
        $record = EagerLoadObject::create(['Title' => 'My obj']);
        $record->write();
        $record->HasManyEagerLoadObjects()->add(HasManyEagerLoadObject::create(['Title' => 'My related obj']));
        $obj = EagerLoadObject::get()->eagerLoad('HasManyEagerLoadObjects')->first();
        $this->assertInstanceOf(EagerLoadedList::class, $obj->HasManyEagerLoadObjects());
    }

    public function testLastHasEagerloadedRelation()
    {
        $record = EagerLoadObject::create(['Title' => 'My obj']);
        $record->write();
        $record->HasManyEagerLoadObjects()->add(HasManyEagerLoadObject::create(['Title' => 'My related obj']));
        $obj = EagerLoadObject::get()->eagerLoad('HasManyEagerLoadObjects')->last();
        $this->assertInstanceOf(EagerLoadedList::class, $obj->HasManyEagerLoadObjects());
    }
}
