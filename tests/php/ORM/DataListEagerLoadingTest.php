<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use LogicException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\EagerLoadedList;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\EagerLoadObject;
use SilverStripe\ORM\Tests\DataListTest\EagerLoading\EagerLoadSubClassObject;
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
use PHPUnit\Framework\Attributes\DataProvider;

class DataListEagerLoadingTest extends SapphireTest
{
    protected $usesDatabase = true;

    private const SHOW_QUERIES_RESET = 'SET_TO_THIS_VALUE_WHEN_FINISHED';

    private $showQueries = DataListEagerLoadingTest::SHOW_QUERIES_RESET;

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

        // The AUTO_INCREMENT functionality isn't abstracted, and doesn't work with the same syntax in
        // other database drivers. But for other drivers we don't care so much if there are overlapping
        // IDs because avoiding them is only required to test the PHP logic, not the database driver
        // compatibility.
        if (!(DB::get_conn() instanceof MySQLDatabase)) {
            return;
        }

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
     * Start counting the number of SELECT database queries being run
     */
    private function startCountingSelectQueries(): void
    {
        if ($this->showQueries !== DataListEagerLoadingTest::SHOW_QUERIES_RESET) {
            throw new LogicException('showQueries wasnt reset, you did something wrong');
        }
        $this->showQueries = $_REQUEST['showqueries'] ?? null;
        // force showqueries on to count the number of SELECT statements via output-buffering
        // if this approach turns out to be too brittle later on, switch to what debugbar
        // does and use tractorcow/proxy-db which should be installed as a dev-dependency
        // https://github.com/lekoala/silverstripe-debugbar/blob/master/code/Collector/DatabaseCollector.php#L79
        $_REQUEST['showqueries'] = 1;
        ob_start();
        echo '__START_ITERATE__';
    }

    /**
     * Stop counting database queries and return the count
     */
    private function stopCountingSelectQueries(): int
    {
        $s = ob_get_clean();
        $s = preg_replace('/.*__START_ITERATE__/s', '', $s);
        $this->resetShowQueries();
        return substr_count($s, ': SELECT');
    }

    /**
     * Reset the "showqueries" request var
     */
    private function resetShowQueries(): void
    {
        if ($this->showQueries === DataListEagerLoadingTest::SHOW_QUERIES_RESET) {
            return;
        }
        if ($this->showQueries) {
            $_REQUEST['showqueries'] = $this->showQueries;
        } else {
            unset($_REQUEST['showqueries']);
        }
        $this->showQueries = DataListEagerLoadingTest::SHOW_QUERIES_RESET;
    }

    #[DataProvider('provideEagerLoadRelations')]
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

    public static function provideEagerLoadRelations(): array
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
        try {
            $this->startCountingSelectQueries();
            $results = [];
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
            $selectCount = $this->stopCountingSelectQueries();
        } finally {
            $this->resetShowQueries();
        }
        return [$results, $selectCount];
    }

    #[DataProvider('provideEagerLoadRelationsEmpty')]
    public function testEagerLoadRelationsEmpty(string $eagerLoadRelation, int $expectedNumQueries): void
    {
        EagerLoadObject::create(['Title' => 'test object'])->write();
        $dataList = EagerLoadObject::get()->eagerLoad($eagerLoadRelation);
        $this->startCountingSelectQueries();
        foreach ($dataList as $record) {
            $relation = $record->$eagerLoadRelation();
            if ($relation instanceof SS_List) {
                // The list should be an empty eagerloaded list
                $this->assertInstanceOf(EagerLoadedList::class, $relation);
                $this->assertCount(0, $relation);
            } elseif ($relation !== null) {
                // There should be no record here
                $this->assertSame($relation->ID, 0);
            }
        }
        $numQueries = $this->stopCountingSelectQueries();
        $this->assertSame($expectedNumQueries, $numQueries);
    }

    public static function provideEagerLoadRelationsEmpty(): array
    {
        return [
            'has_one' => [
                'eagerLoadRelation' => 'HasOneEagerLoadObject',
                'expectedNumQueries' => 1,
            ],
            'polymorph_has_one' => [
                'eagerLoadRelation' => 'HasOnePolymorphObject',
                'expectedNumQueries' => 1,
            ],
            'belongs_to' => [
                'eagerLoadRelation' => 'BelongsToEagerLoadObject',
                'expectedNumQueries' => 2,
            ],
            'has_many' => [
                'eagerLoadRelation' => 'HasManyEagerLoadObjects',
                'expectedNumQueries' => 2,
            ],
            'many_many' => [
                'eagerLoadRelation' => 'ManyManyEagerLoadObjects',
                'expectedNumQueries' => 2,
            ],
            'many_many through' => [
                'eagerLoadRelation' => 'ManyManyThroughEagerLoadObjects',
                'expectedNumQueries' => 2,
            ],
            'belongs_many_many' => [
                'eagerLoadRelation' => 'BelongsManyManyEagerLoadObjects',
                'expectedNumQueries' => 2,
            ],
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

    #[DataProvider('provideEagerLoadInvalidRelationException')]
    public function testEagerLoadInvalidRelationException(string $eagerLoadRelation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid relation passed to eagerLoad() - $eagerLoadRelation");
        $this->createEagerLoadData();
        EagerLoadObject::get()->eagerLoad($eagerLoadRelation)->toArray();
    }

    public static function provideEagerLoadInvalidRelationException(): array
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

    #[DataProvider('provideEagerLoadManyManyExtraFields')]
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

    public static function provideEagerLoadManyManyExtraFields(): array
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

    #[DataProvider('provideEagerLoadManyManyThroughJoinRecords')]
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

    public static function provideEagerLoadManyManyThroughJoinRecords(): array
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

    #[DataProvider('provideEagerLoadRelations')]
    public function testEagerLoadingFilteredList(string $iden, array $eagerLoad, int $expected): void
    {
        $this->createEagerLoadData(5);
        $filter = ['Title:GreaterThan' => 'obj 0'];
        $dataList = EagerLoadObject::get()->filter($filter)->eagerLoad($eagerLoad);

        // Validate that filtering results still actually works on the base list
        $this->assertListEquals([
            ['Title' => 'obj 1'],
            ['Title' => 'obj 2'],
            ['Title' => 'obj 3'],
            ['Title' => 'obj 4'],
        ], $dataList);

        $this->validateEagerLoadingResults($iden, EagerLoadObject::get()->filter($filter), $dataList);
    }

    #[DataProvider('provideEagerLoadRelations')]
    public function testEagerLoadingSortedList(string $iden, array $eagerLoad, int $expected): void
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

    #[DataProvider('provideEagerLoadRelations')]
    public function testEagerLoadingLimitedList(string $iden, array $eagerLoad, int $expected): void
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

    #[DataProvider('provideEagerLoadRelations')]
    public function testRepeatedIterationOfEagerLoadedList(string $iden, array $eagerLoad, int $expected): void
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
     */
    #[DataProvider('provideEagerLoadRelations')]
    public function testEagerLoadWorksAnywhereBeforeExecution(string $iden, array $eagerLoad, int $expected): void
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

    #[DataProvider('provideEagerLoadRelations')]
    public function testEagerLoadWithChunkedFetch(string $iden, array $eagerLoad, int $expected): void
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

    #[DataProvider('provideEagerLoadingEmptyRelations')]
    public function testEagerLoadingEmptyRelations(string $iden, string $eagerLoad): void
    {
        $numBaseRecords = 3;
        $numLevel1Records = 2;
        $numLevel2Records = 2;
        // Similar to createEagerLoadData(), except with less relations and
        // making sure only the first record has any items in its relation lists.
        for ($i = 0; $i < $numBaseRecords; $i++) {
            // base object
            $obj = new EagerLoadObject();
            $obj->Title = "obj $i";
            $objID = $obj->write();
            if ($i > 1) {
                continue;
            }
            // has_one - level1
            $hasOneObj = new HasOneEagerLoadObject();
            $hasOneObj->Title = "hasOneObj $i";
            $hasOneObjID = $hasOneObj->write();
            $obj->HasOneEagerLoadObjectID = $hasOneObjID;
            $obj->write();
            // belongs_to - level1
            $belongsToObj = new BelongsToEagerLoadObject();
            $belongsToObj->EagerLoadObjectID = $objID;
            $belongsToObj->Title = "belongsToObj $i";
            $belongsToObjID = $belongsToObj->write();
            if ($i > 0) {
                continue;
            }
            // has_one - level2
            $hasOneSubObj = new HasOneSubEagerLoadObject();
            $hasOneSubObj->Title = "hasOneSubObj $i";
            $hasOneSubObjID = $hasOneSubObj->write();
            $hasOneObj->HasOneSubEagerLoadObjectID = $hasOneSubObjID;
            $hasOneObj->write();
            // belongs_to - level2
            $belongsToSubObj = new BelongsToSubEagerLoadObject();
            $belongsToSubObj->BelongsToEagerLoadObjectID = $belongsToObjID;
            $belongsToSubObj->Title = "belongsToSubObj $i";
            $belongsToSubObj->write();
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

        // The actual test starts here - everything above is for creating fixtures
        $i = 0;
        $relations = explode('.', $eagerLoad);
        try {
            foreach (EagerLoadObject::get()->eagerLoad($eagerLoad) as $parentRecord) {
                if ($i === 0) {
                    $this->startCountingSelectQueries();
                }

                // Test first level items are handled correctly
                $relation = $relations[0];
                $listOrRecord = $parentRecord->$relation();
                if (str_starts_with($iden, 'hasone') || str_starts_with($iden, 'belongsto')) {
                    $class = str_starts_with($iden, 'hasone') ? HasOneEagerLoadObject::class : BelongsToEagerLoadObject::class;
                    if ($i > 1) {
                        $this->assertSame(0, $listOrRecord->ID);
                    }
                    $this->assertInstanceOf($class, $listOrRecord);
                } else {
                    // For any record after the first one, there should be nothing in the related list.
                    // All lists, even empty ones, should be an instance of EagerLoadedList
                    $this->assertCount($i > 0 ? 0 : 2, $listOrRecord);
                    $this->assertInstanceOf(EagerLoadedList::class, $listOrRecord);
                }
                $i++;

                // Test second level items are handled correctly
                if (count($relations) > 1) {
                    $j = 0;
                    $relation = $relations[1];
                    if (str_starts_with($iden, 'hasone') || str_starts_with($iden, 'belongsto')) {
                        $record2 = $listOrRecord->$relation();
                        $class = str_starts_with($iden, 'hasone') ? HasOneSubEagerLoadObject::class : BelongsToSubEagerLoadObject::class;
                        if ($j > 0) {
                            $this->assertSame(0, $record2->ID);
                        }
                        $this->assertInstanceOf($class, $record2);
                    } else {
                        // For any record after the first one, there should be nothing in the related list.
                        // All lists, even empty ones, should be an instance of EagerLoadedList
                        foreach ($listOrRecord as $relatedRecord) {
                            $list2 = $relatedRecord->$relation();
                            $this->assertCount($j > 0 ? 0 : 2, $list2);
                            $this->assertInstanceOf(EagerLoadedList::class, $list2);
                            $j++;
                        }
                    }
                }
            }
            // No queries should have been run after initiating the loop
            $this->assertSame(0, $this->stopCountingSelectQueries());
        } finally {
            $this->resetShowQueries();
        }
    }

    public static function provideEagerLoadingEmptyRelations(): array
    {
        return [
            [
                'hasone-onelevel',
                'HasOneEagerLoadObject',
            ],
            [
                'hasone-twolevels',
                'HasOneEagerLoadObject.HasOneSubEagerLoadObject',
            ],
            [
                'belongsto-onelevel',
                'BelongsToEagerLoadObject',
            ],
            [
                'belongsto-twolevels',
                'BelongsToEagerLoadObject.BelongsToSubEagerLoadObject',
            ],
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

    /**
     * Tests that if the same record exists in multiple relations, its data is
     * eagerloaded without extra unnecessary queries.
     */
    public function testEagerLoadingSharedRelations()
    {
        $record1 = EagerLoadObject::create(['Title' => 'My obj1']);
        $record1->write();
        $record2 = EagerLoadObject::create(['Title' => 'My obj2']);
        $record2->write();
        $manyMany = ManyManyEagerLoadObject::create(['Title' => 'My manymany']);
        $manyMany->write();
        $record1->ManyManyEagerLoadObjects()->add($manyMany);
        $record2->ManyManyEagerLoadObjects()->add($manyMany);
        $subManyMany = ManyManySubEagerLoadObject::create(['Title' => 'My submanymany']);
        $subManyMany->write();
        $manyMany->ManyManySubEagerLoadObjects()->add($subManyMany);

        $eagerLoadQuery = EagerLoadObject::get()
            ->filter(['ID' => [$record1->ID, $record2->ID]])
            ->eagerLoad('ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects');
        $loop1Count = 0;
        $loop2Count = 0;
        foreach ($eagerLoadQuery as $record) {
            $loop1Count++;
            $eagerLoaded1 = $record->ManyManyEagerLoadObjects();
            $this->assertInstanceOf(EagerLoadedList::class, $eagerLoaded1);
            foreach ($eagerLoaded1 as $manyManyRecord) {
                $loop2Count++;
                $eagerLoaded2 = $manyManyRecord->ManyManySubEagerLoadObjects();
                $this->assertInstanceOf(EagerLoadedList::class, $eagerLoaded2);
            }
        }
        $this->assertGreaterThan(1, $loop1Count);
        $this->assertGreaterThan(1, $loop2Count);
    }

    public function testInvalidAssociativeArray(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Value of associative array must be a callable.'
            . 'If you don\'t want to pre-filter the list, use an indexed array.'
        );
        EagerLoadObject::get()->eagerLoad(['HasManyEagerLoadObjects' => 'HasManyEagerLoadObjects']);
    }

    public static function provideNoLimitEagerLoadingQuery(): array
    {
        // Note we don't test has_one or belongs_to because those don't accept a callback at all.
        return [
            'limit list directly - has_many' => [
                'relation' => 'HasManyEagerLoadObjects',
                'relationType' => 'has_many',
                'callback' => fn (DataList $list) => $list->limit(1),
            ],
            'limit list directly - many_many' => [
                'relation' => 'ManyManyEagerLoadObjects',
                'relationType' => 'many_many',
                'callback' => fn (DataList $list) => $list->limit(1),
            ],
            'limit underlying dataquery - has_many' => [
                'relation' => 'HasManyEagerLoadObjects',
                'relationType' => 'has_many',
                'callback' => fn (DataList $list) => $list->alterDataQuery(fn (DataQuery $query) => $query->limit(1)),
            ],
            'limit underlying dataquery - many_many' => [
                'relation' => 'ManyManyEagerLoadObjects',
                'relationType' => 'many_many',
                'callback' => fn (DataList $list) => $list->alterDataQuery(fn (DataQuery $query) => $query->limit(1)),
            ],
        ];
    }

    /**
     * Tests that attempting to limit an eagerloading query will throw an exception.
     */
    #[DataProvider('provideNoLimitEagerLoadingQuery')]
    public function testNoLimitEagerLoadingQuery(string $relation, string $relationType, callable $callback): void
    {
        // Need to have at least one record in the main list for eagerloading to even be triggered.
        $record = new EagerLoadObject();
        $record->write();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Cannot apply limit to eagerloaded data for $relationType relation $relation."
        );
        EagerLoadObject::get()->eagerLoad([$relation => $callback])->toArray();
    }

    public static function provideCannotManipulateUnaryRelationQuery(): array
    {
        return [
            'has_one' => [
                'relation' => 'HasOneEagerLoadObject',
                'relationType' => 'has_one',
            ],
            'belongs_to' => [
                'relation' => 'BelongsToEagerLoadObject',
                'relationType' => 'belongs_to',
            ],
        ];
    }

    /**
     * Tests that attempting to manipulate a has_one or belongs_to eagerloading query will throw an exception.
     */
    #[DataProvider('provideCannotManipulateUnaryRelationQuery')]
    public function testCannotManipulateUnaryRelationQuery(string $relation, string $relationType): void
    {
        // Need to have at least one record in the main list for eagerloading to even be triggered.
        $record = new EagerLoadObject();
        $record->write();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Cannot manipulate eagerloading query for $relationType relation $relation"
        );
        EagerLoadObject::get()->eagerLoad([$relation => fn (DataList $list) => $list])->toArray();
    }

    /**
     * Tests that attempting to manipulate an eagerloading query without returning the list will throw an exception.
     */
    public function testManipulatingEagerloadingQueryNoReturn(): void
    {
        // Need to have at least one record in the main list for eagerloading to even be triggered.
        $record = new EagerLoadObject();
        $record->write();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Eagerloading callback for has_many relation HasManyEagerLoadObjects must return a DataList.'
        );
        EagerLoadObject::get()->eagerLoad([
            'HasManyEagerLoadObjects' => function (DataList $list) {
                $list->filter('ID', 1);
            }
        ])->toArray();
    }

    public static function provideManipulatingEagerloadingQuery(): array
    {
        return [
            'nested has_many' => [
                'relationType' => 'has_many',
                'relations' => [
                    'HasManyEagerLoadObjects' => HasManyEagerLoadObject::class,
                    'HasManySubEagerLoadObjects' => HasManySubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'HasManyEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'HasMany T'])->Sort('Title', 'ASC'),
                    'HasManyEagerLoadObjects.HasManySubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'DESC']),
                ],
                'expected' => [
                    'first loop' => ['HasMany Three', 'HasMany Two'],
                    'second loop' => ['Sub B', 'Sub A'],
                ],
            ],
            'nested has_many (reverse sort)' => [
                'relationType' => 'has_many',
                'relations' => [
                    'HasManyEagerLoadObjects' => HasManyEagerLoadObject::class,
                    'HasManySubEagerLoadObjects' => HasManySubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'HasManyEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'HasMany T'])->Sort('Title', 'DESC'),
                    'HasManyEagerLoadObjects.HasManySubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'ASC']),
                ],
                'expected' => [
                    'first loop' => ['HasMany Two', 'HasMany Three'],
                    'second loop' => ['Sub A', 'Sub B'],
                ],
            ],
            'nested many_many' => [
                'relationType' => 'many_many',
                'relations' => [
                    'ManyManyEagerLoadObjects' => ManyManyEagerLoadObject::class,
                    'ManyManySubEagerLoadObjects' => ManyManySubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'ManyManyEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'ManyMany T'])->Sort('Title', 'ASC'),
                    'ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'DESC']),
                ],
                'expected' => [
                    'first loop' => ['ManyMany Three', 'ManyMany Two'],
                    'second loop' => ['Sub B', 'Sub A'],
                ],
            ],
            'nested many_many (reverse sort)' => [
                'relationType' => 'many_many',
                'relations' => [
                    'ManyManyEagerLoadObjects' => ManyManyEagerLoadObject::class,
                    'ManyManySubEagerLoadObjects' => ManyManySubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'ManyManyEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'ManyMany T'])->Sort('Title', 'DESC'),
                    'ManyManyEagerLoadObjects.ManyManySubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'ASC']),
                ],
                'expected' => [
                    'first loop' => ['ManyMany Two', 'ManyMany Three'],
                    'second loop' => ['Sub A', 'Sub B'],
                ],
            ],
            'nested belongs_many_many' => [
                'relationType' => 'belongs_many_many',
                'relations' => [
                    'BelongsManyManyEagerLoadObjects' => BelongsManyManyEagerLoadObject::class,
                    'BelongsManyManySubEagerLoadObjects' => BelongsManyManySubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'BelongsManyManyEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'ManyMany T'])->Sort('Title', 'ASC'),
                    'BelongsManyManyEagerLoadObjects.BelongsManyManySubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'DESC']),
                ],
                'expected' => [
                    'first loop' => ['ManyMany Three', 'ManyMany Two'],
                    'second loop' => ['Sub B', 'Sub A'],
                ],
            ],
            'nested belongs_many_many (reverse sort)' => [
                'relationType' => 'belongs_many_many',
                'relations' => [
                    'BelongsManyManyEagerLoadObjects' => BelongsManyManyEagerLoadObject::class,
                    'BelongsManyManySubEagerLoadObjects' => BelongsManyManySubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'BelongsManyManyEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'ManyMany T'])->Sort('Title', 'DESC'),
                    'BelongsManyManyEagerLoadObjects.BelongsManyManySubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'ASC']),
                ],
                'expected' => [
                    'first loop' => ['ManyMany Two', 'ManyMany Three'],
                    'second loop' => ['Sub A', 'Sub B'],
                ],
            ],
            'nested many_many_through' => [
                'relationType' => 'many_many_through',
                'relations' => [
                    'ManyManyThroughEagerLoadObjects' => ManyManyThroughEagerLoadObject::class,
                    'ManyManyThroughSubEagerLoadObjects' => ManyManyThroughSubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'ManyManyThroughEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'ManyMany T'])->Sort('Title', 'ASC'),
                    'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'DESC']),
                ],
                'expected' => [
                    'first loop' => ['ManyMany Three', 'ManyMany Two'],
                    'second loop' => ['Sub B', 'Sub A'],
                ],
            ],
            'nested many_many_through (reverse sort)' => [
                'relationType' => 'many_many_through',
                'relations' => [
                    'ManyManyThroughEagerLoadObjects' => ManyManyThroughEagerLoadObject::class,
                    'ManyManyThroughSubEagerLoadObjects' => ManyManyThroughSubEagerLoadObject::class,
                ],
                'eagerLoad' => [
                    'ManyManyThroughEagerLoadObjects' => fn (DataList $list) => $list->filter(['Title:StartsWith' => 'ManyMany T'])->Sort('Title', 'DESC'),
                    'ManyManyThroughEagerLoadObjects.ManyManyThroughSubEagerLoadObjects' => fn (DataList $list) => $list->Sort(['Title' => 'ASC']),
                ],
                'expected' => [
                    'first loop' => ['ManyMany Two', 'ManyMany Three'],
                    'second loop' => ['Sub A', 'Sub B'],
                ],
            ],
        ];
    }

    /**
     * Tests that callbacks can be used to manipulate eagerloading queries
     */
    #[DataProvider('provideManipulatingEagerloadingQuery')]
    public function testManipulatingEagerloadingQuery(string $relationType, array $relations, array $eagerLoad, array $expected): void
    {
        $relationNames = array_keys($relations);
        $relationOne = $relationNames[0];
        $relationTwo = $relationNames[1];
        $classOne = $relations[$relationOne];
        $classTwo = $relations[$relationTwo];
        // Prepare fixtures.
        // Eager loading is different to most tests - we build fixtures at run time per test
        // to avoid wasting a bunch of CI time building test-specific YAML fixtures.
        $record = new EagerLoadObject();
        $record->write();
        if ($relationType === 'has_many') {
            $hasMany1 = new $classOne(['Title' => 'HasMany One']);
            $hasMany2 = new $classOne(['Title' => 'HasMany Two']);
            $hasMany3 = new $classOne(['Title' => 'HasMany Three']);
            $hasMany = [$hasMany1, $hasMany2, $hasMany3];
            foreach ($hasMany as $hasManyRecord) {
                $hasManyRecord->write();
                // Since these are has_many they can't share the same records, so build
                // separate records for each list.
                $hasManySub1 = new $classTwo(['Title' => 'Sub A']);
                $hasManySub2 = new $classTwo(['Title' => 'Sub B']);
                $hasManySub1->write();
                $hasManySub2->write();
                $hasManyRecord->$relationTwo()->addMany([$hasManySub1, $hasManySub2]);
            }
            $record->$relationOne()->addMany($hasMany);
        } elseif (str_contains($relationType, 'many_many')) {
            $manyMany1 = new $classOne(['Title' => 'ManyMany One']);
            $manyMany2 = new $classOne(['Title' => 'ManyMany Two']);
            $manyMany3 = new $classOne(['Title' => 'ManyMany Three']);
            $manyManySub1 = new $classTwo(['Title' => 'Sub A']);
            $manyManySub2 = new $classTwo(['Title' => 'Sub B']);
            $manyManySub1->write();
            $manyManySub2->write();
            $manyMany = [$manyMany1, $manyMany2, $manyMany3];
            foreach ($manyMany as $manyManyRecord) {
                $manyManyRecord->write();
                $manyManyRecord->$relationTwo()->addMany([$manyManySub1, $manyManySub2]);
            }
            $record->$relationOne()->addMany($manyMany);
        } else {
            throw new LogicException("Unexpected relation type: $relationType");
        }

        // Loop through the relations and make assertions
        foreach (EagerLoadObject::get()->filter(['ID' => $record->ID])->eagerLoad($eagerLoad) as $eagerLoadObject) {
            $list = $eagerLoadObject->$relationOne();
            $this->assertInstanceOf(EagerLoadedList::class, $list);
            $this->assertSame($expected['first loop'], $list->column('Title'));
            foreach ($list as $relatedObject) {
                $list = $relatedObject->$relationTwo();
                $this->assertInstanceOf(EagerLoadedList::class, $list);
                $this->assertSame($expected['second loop'], $list->column('Title'));
            }
        }
    }

    public function testHasOneMultipleAppearance(): void
    {
        $items = $this->provideHasOneObjects();
        $this->validateMultipleAppearance($items, 6, EagerLoadObject::get());
        $this->validateMultipleAppearance($items, 2, EagerLoadObject::get()->eagerLoad('HasOneEagerLoadObject'));
    }

    protected function provideHasOneObjects(): array
    {
        $subA = new HasOneEagerLoadObject();
        $subA->Title = 'A';
        $subA->write();

        $subB = new HasOneEagerLoadObject();
        $subB->Title = 'B';
        $subB->write();

        $subC = new HasOneEagerLoadObject();
        $subC->Title = 'C';
        $subC->write();

        $baseA = new EagerLoadObject();
        $baseA->Title = 'A';
        $baseA->HasOneEagerLoadObjectID = $subA->ID;
        $baseA->write();

        $baseB = new EagerLoadObject();
        $baseB->Title = 'B';
        $baseB->HasOneEagerLoadObjectID = $subA->ID;
        $baseB->write();

        $baseC = new EagerLoadObject();
        $baseC->Title = 'C';
        $baseC->HasOneEagerLoadObjectID = $subB->ID;
        $baseC->write();

        $baseD = new EagerLoadObject();
        $baseD->Title = 'D';
        $baseD->HasOneEagerLoadObjectID = $subC->ID;
        $baseD->write();

        $baseE = new EagerLoadObject();
        $baseE->Title = 'E';
        $baseE->HasOneEagerLoadObjectID = $subB->ID;
        $baseE->write();

        $baseF = new EagerLoadObject();
        $baseF->Title = 'F';
        $baseF->HasOneEagerLoadObjectID = 0;
        $baseF->write();

        return [
            $baseA->ID => [$subA->ClassName, $subA->ID],
            $baseB->ID => [$subA->ClassName, $subA->ID],
            $baseC->ID => [$subB->ClassName, $subB->ID],
            $baseD->ID => [$subC->ClassName, $subC->ID],
            $baseE->ID => [$subB->ClassName, $subB->ID],
            $baseF->ID => [null, 0],
        ];
    }

    public function testPolymorphEagerLoading(): void
    {
        $items = $this->providePolymorphHasOne();
        $this->validateMultipleAppearance($items, 5, EagerLoadObject::get(), 'HasOnePolymorphObject');
        $this->validateMultipleAppearance($items, 4, EagerLoadObject::get()->eagerLoad('HasOnePolymorphObject'), 'HasOnePolymorphObject');
    }

    protected function providePolymorphHasOne(): array
    {
        $subA = new HasOneEagerLoadObject();
        $subA->Title = 'A';
        $subA->write();

        $subB = new HasOneEagerLoadObject();
        $subB->Title = 'B';
        $subB->write();

        $subC = new HasOneSubSubEagerLoadObject();
        $subC->Title = 'C';
        $subC->write();

        $subD = new EagerLoadSubClassObject();
        $subD->Title = 'D';
        $subD->write();

        $baseA = new EagerLoadObject();
        $baseA->Title = 'A';
        $baseA->HasOnePolymorphObjectClass = $subA->ClassName;
        $baseA->HasOnePolymorphObjectID = $subA->ID;
        $baseA->write();

        $baseB = new EagerLoadObject();
        $baseB->Title = 'B';
        $baseB->HasOnePolymorphObjectClass = $subB->ClassName;
        $baseB->HasOnePolymorphObjectID = $subB->ID;
        $baseB->write();

        $baseC = new EagerLoadObject();
        $baseC->Title = 'C';
        $baseC->HasOnePolymorphObjectClass = $subC->ClassName;
        $baseC->HasOnePolymorphObjectID = $subC->ID;
        $baseC->write();

        $baseD = new EagerLoadObject();
        $baseD->Title = 'D';
        $baseD->HasOnePolymorphObjectClass = $subD->ClassName;
        $baseD->HasOnePolymorphObjectID = $subD->ID;
        $baseD->write();

        $baseE = new EagerLoadObject();
        $baseE->Title = 'E';
        $baseE->HasOnePolymorphObjectClass = null;
        $baseE->HasOnePolymorphObjectID = 0;
        $baseE->write();

        return [
            $baseA->ID => [$subA->ClassName, $subA->ID],
            $baseB->ID => [$subB->ClassName, $subB->ID],
            $baseC->ID => [$subC->ClassName, $subC->ID],
            $baseD->ID => [$subD->ClassName, $subD->ID],
            $baseE->ID => [null, 0],
        ];
    }

    protected function validateMultipleAppearance(
        array $expectedRelations,
        int $expected,
        DataList $list,
        string $relation = 'HasOneEagerLoadObject',
    ): void {
        try {
            $this->startCountingSelectQueries();

            /** @var EagerLoadObject $item */
            foreach ($list as $item) {
                $rel = $item->{$relation}();

                $this->assertArrayHasKey($item->ID, $expectedRelations, $relation . ' should be loaded');
                $this->assertEquals($expectedRelations[$item->ID][0], $rel?->ID ? $rel?->ClassName : null);
                $this->assertEquals($expectedRelations[$item->ID][1], $rel?->ID ?? 0);
            }

            $this->assertSame($expected, $this->stopCountingSelectQueries());
        } finally {
            $this->resetShowQueries();
        }
    }
}
