<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataList;
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

    // Borrow the model from DataObjectTest
    // protected static $fixture_file = 'DataObjectTest.yml';

    protected $usesDatabase = true;

    public static function getExtraDataObjects()
    {
        $eagerLoadDataObjects = [
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
        return array_merge(
            // DataObjectTest::$extra_data_objects,
            // ManyManyListTest::$extra_data_objects,
            $eagerLoadDataObjects
        );
    }

    /**
     * @dataProvider provideEagerLoadRelations
     */
    public function testEagerLoadRelations(string $iden, array $eagerLoad, int $expected): void
    {
        $this->createEagerLoadData();
        $dataList = EagerLoadObject::get()->eagerLoad(...$eagerLoad);
        list($results, $selectCount) = $this->iterateEagerLoadData($dataList);
        $expectedResults = $this->expectedEagerLoadData();
        // Sort because the order of the results doesn't really matter - and has proven to be different in postgres
        sort($expectedResults);
        sort($results);

        $this->assertSame($expectedResults, $results);
        $this->assertSame($expected, $selectCount);
    }

    public function provideEagerLoadRelations(): array
    {
        return [
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

    private function createEagerLoadData(): void
    {
        for ($i = 0; $i < 2; $i++) {
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
            for ($j = 0; $j < 2; $j++) {
                $hasManyObj = new HasManyEagerLoadObject();
                $hasManyObj->EagerLoadObjectID = $objID;
                $hasManyObj->Title = "hasManyObj $i $j";
                $hasManyObjID = $hasManyObj->write();
                for ($k = 0; $k < 2; $k++) {
                    $hasManySubObj = new HasManySubEagerLoadObject();
                    $hasManySubObj->HasManyEagerLoadObjectID = $hasManyObjID;
                    $hasManySubObj->Title = "hasManySubObj $i $j $k";
                    $hasManySubObjID = $hasManySubObj->write();
                    for ($l = 0; $l < 2; $l++) {
                        $hasManySubSubObj = new HasManySubSubEagerLoadObject();
                        $hasManySubSubObj->HasManySubEagerLoadObjectID = $hasManySubObjID;
                        $hasManySubSubObj->Title = "hasManySubSubObj $i $j $k $l";
                        $hasManySubSubObj->write();
                    }
                }
            }
            // many_many
            for ($j = 0; $j < 2; $j++) {
                $manyManyObj = new ManyManyEagerLoadObject();
                $manyManyObj->Title = "manyManyObj $i $j";
                $manyManyObj->write();
                $obj->ManyManyEagerLoadObjects()->add($manyManyObj);
                for ($k = 0; $k < 2; $k++) {
                    $manyManySubObj = new ManyManySubEagerLoadObject();
                    $manyManySubObj->Title = "manyManySubObj $i $j $k";
                    $manyManySubObj->write();
                    $manyManyObj->ManyManySubEagerLoadObjects()->add($manyManySubObj);
                    for ($l = 0; $l < 2; $l++) {
                        $manyManySubSubObj = new ManyManySubSubEagerLoadObject();
                        $manyManySubSubObj->Title = "manyManySubSubObj $i $j $k $l";
                        $manyManySubSubObj->write();
                        $manyManySubObj->ManyManySubSubEagerLoadObjects()->add($manyManySubSubObj);
                    }
                }
            }
            // many_many_through
            for ($j = 0; $j < 2; $j++) {
                $manyManyThroughObj = new ManyManyThroughEagerLoadObject();
                $manyManyThroughObj->Title = "manyManyThroughObj $i $j";
                $manyManyThroughObj->write();
                $obj->ManyManyThroughEagerLoadObjects()->add($manyManyThroughObj);
                for ($k = 0; $k < 2; $k++) {
                    $manyManyThroughSubObj = new ManyManyThroughSubEagerLoadObject();
                    $manyManyThroughSubObj->Title = "manyManyThroughSubObj $i $j $k";
                    $manyManyThroughSubObj->write();
                    $manyManyThroughObj->ManyManyThroughSubEagerLoadObjects()->add($manyManyThroughSubObj);
                    for ($l = 0; $l < 2; $l++) {
                        $manyManyThroughSubSubObj = new ManyManyThroughSubSubEagerLoadObject();
                        $manyManyThroughSubSubObj->Title = "manyManyThroughSubSubObj $i $j $k $l";
                        $manyManyThroughSubSubObj->write();
                        $manyManyThroughSubObj->ManyManyThroughSubSubEagerLoadObjects()->add($manyManyThroughSubSubObj);
                    }
                }
            }
            // belongs_many_many
            for ($j = 0; $j < 2; $j++) {
                $belongsManyManyObj = new BelongsManyManyEagerLoadObject();
                $belongsManyManyObj->Title = "belongsManyManyObj $i $j";
                $belongsManyManyObj->write();
                $obj->BelongsManyManyEagerLoadObjects()->add($belongsManyManyObj);
                for ($k = 0; $k < 2; $k++) {
                    $belongsManyManySubObj = new BelongsManyManySubEagerLoadObject();
                    $belongsManyManySubObj->Title = "belongsManyManySubObj $i $j $k";
                    $belongsManyManySubObj->write();
                    $belongsManyManyObj->BelongsManyManySubEagerLoadObjects()->add($belongsManyManySubObj);
                    for ($l = 0; $l < 2; $l++) {
                        $belongsManyManySubSubObj = new BelongsManyManySubSubEagerLoadObject();
                        $belongsManyManySubSubObj->Title = "belongsManyManySubSubObj $i $j $k $l";
                        $belongsManyManySubSubObj->write();
                        $belongsManyManySubObj->BelongsManyManySubSubEagerLoadObjects()->add($belongsManyManySubSubObj);
                    }
                }
            }
            // mixed
            for ($j = 0; $j < 2; $j++) {
                $mixedManyManyObj = new MixedManyManyEagerLoadObject();
                $mixedManyManyObj->Title = "mixedManyManyObj $i $j";
                $mixedManyManyObj->write();
                $obj->MixedManyManyEagerLoadObjects()->add($mixedManyManyObj);
                for ($k = 0; $k < 2; $k++) {
                    $mixedHasManyObj = new MixedHasManyEagerLoadObject();
                    $mixedHasManyObj->Title = "mixedHasManyObj $i $j $k";
                    $mixedHasManyObjID = $mixedHasManyObj->write();
                    $mixedManyManyObj->MixedHasManyEagerLoadObjects()->add($mixedHasManyObj);
                    for ($l = 0; $l < 2; $l++) {
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

    private function iterateEagerLoadData(DataList $dataList): array
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

    private function expectedEagerLoadData(): array
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
}
