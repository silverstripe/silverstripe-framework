<?php

namespace SilverStripe\ORM\Tests;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\MySQLiConnector;
use SilverStripe\ORM\EagerLoadedList;
use SilverStripe\ORM\DB;
use SilverStripe\Model\List\Filterable;
use SilverStripe\ORM\Tests\DataObjectTest\EquipmentCompany;
use SilverStripe\ORM\Tests\DataObjectTest\Fan;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Sortable;
use SilverStripe\ORM\Tests\DataObjectTest\SubTeam;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\DataObjectTest\TeamComment;
use SilverStripe\ORM\Tests\DataObjectTest\ValidatedObject;
use SilverStripe\ORM\Tests\ManyManyListTest\Category;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBPrimaryKey;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\Tests\DataObjectTest\RelationChildFirst;
use SilverStripe\ORM\Tests\DataObjectTest\RelationChildSecond;
use PHPUnit\Framework\Attributes\DataProvider;

class EagerLoadedListTest extends SapphireTest
{
    // Borrow the model from DataObjectTest
    protected static $fixture_file = 'DataObjectTest.yml';

    public static function getExtraDataObjects()
    {
        return DataListTest::getExtraDataObjects();
    }

    private static function getBasicRecordRows(): array
    {
        return [
            [
                'ID' => 1,
                'Name' => 'test obj 1',
                'Created' => '2013-01-01 00:00:00',
                'SomeField' => 'VaLuE',
            ],
            [
                'ID' => 2,
                'Name' => 'test obj 2',
                'Created' => '2023-01-01 00:00:00',
                'SomeField' => 'value',
            ],
            [
                'ID' => 3,
                'Name' => 'test obj 3',
                'Created' => '2023-01-01 00:00:00',
                'SomeField' => null,
            ],
        ];
    }

    private function getListWithRecords(
        string|DataList $data,
        string $dataListClass = DataList::class,
        ?int $foreignID = null,
        ?array $manyManyComponentData = null
    ): EagerLoadedList {
        // Get some garbage values for the manymany component so we don't get errors
        // If the component is actually needed, it'll be passed in
        if ($manyManyComponentData === null) {
            $manyManyComponent = [];
            if (in_array($dataListClass, [ManyManyThroughList::class, ManyManyList::class])) {
                $manyManyComponent['join'] = DataObject::class;
                $manyManyComponent['childField'] = '';
                $manyManyComponent['parentField'] = '';
                $manyManyComponent['parentClass'] = DataObject::class;
                $manyManyComponent['extraFields'] = [];
            }
        } else {
            list($parentClass, $relationName) = $manyManyComponentData;
            $manyManyComponent = DataObject::getSchema()->manyManyComponent($parentClass, $relationName);
            $manyManyComponent['extraFields'] = DataObject::getSchema()->manyManyExtraFieldsForComponent($parentClass, $relationName);
        }

        if ($data instanceof DataList) {
            $dataClass = $data->dataClass();
            $query = $data;
        } else {
            $dataClass = $data;
            $query = DataObject::get($dataClass);
        }
        if ($foreignID === null && $dataListClass !== DataList::class) {
            $foreignID = 9999;
        }
        $list = new EagerLoadedList($dataClass, $dataListClass, $foreignID, $manyManyComponent);
        foreach ($query->dataQuery()->execute() as $row) {
            $list->addRow($row);
        }
        return $list;
    }

    public function testHasID()
    {
        $list = new EagerLoadedList(Sortable::class, DataList::class);
        foreach (EagerLoadedListTest::getBasicRecordRows() as $row) {
            $list->addRow($row);
        }
        $this->assertTrue($list->hasID(3));
        $this->assertFalse($list->hasID(999));
    }

    public function testDataClass()
    {
        $dataClass = TeamComment::class;
        $list = new EagerLoadedList($dataClass, DataList::class);
        $this->assertEquals(TeamComment::class, $list->dataClass());
    }

    public function testDataClassCaseInsensitive()
    {
        $dataClass = strtolower(TeamComment::class);
        $list = new EagerLoadedList($dataClass, DataList::class);
        $list->addRow(['ID' => 1]);
        $this->assertInstanceOf($dataClass, $list->first());
    }

    public function testClone()
    {
        $list = new EagerLoadedList(ValidatedObject::class, DataList::class);
        $list->addRow(['ID' => 1]);
        $clone = clone($list);

        $this->assertEquals($list, $clone);
        $this->assertEquals($list->column(), $clone->column());

        $clone->addRow(['ID' => 2]);
        $this->assertNotEquals($list->column(), $clone->column());
    }

    public function testDbObject()
    {
        $list = new EagerLoadedList(TeamComment::class, DataList::class);
        $this->assertInstanceOf(DBPrimaryKey::class, $list->dbObject('ID'));
        $this->assertInstanceOf(DBVarchar::class, $list->dbObject('Name'));
        $this->assertInstanceOf(DBText::class, $list->dbObject('Comment'));
    }

    public function testGetIDList()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $idList = $list->getIDList();
        $this->assertSame($list->column('ID'), array_keys($idList));
        $this->assertSame($list->column('ID'), array_values($idList));
    }

    public function testSetByIDList()
    {
        $list = new EagerLoadedList(TeamComment::class, DataList::class);
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't set the ComponentSet on an EagerLoadedList");
        $list->setByIDList([1,2,3]);
    }

    public function testForForeignID()
    {
        $list = new EagerLoadedList(TeamComment::class, DataList::class);
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't change the foreign ID for an EagerLoadedList");
        $list->forForeignID(1);
    }

    /**
     * Also tests addRows at the same time
     */
    public function testGetRows()
    {
        $list = new EagerLoadedList(TeamComment::class, DataList::class);
        $rows = [
            [
                'ID' => 202,
                'Name' => 'Wobuffet',
            ],
            [
                'ID' => 25,
                'Name' => 'Pikachu',
            ]
        ];
        $list->addRows($rows);
        $this->assertSame($rows, $list->getRows());

        // Check we can still add them on afterward
        $newRow = [
            'ID' => 1,
            'Name' => 'Bulbasaur'
        ];
        $rows[] = $newRow;
        $list->addRows([$newRow]);
        $this->assertSame($rows, $list->getRows());
    }

    #[DataProvider('provideAddRowBadID')]
    public function testAddRowBadID(array $row)
    {
        $list = new EagerLoadedList(TeamComment::class, DataList::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$row must have a valid ID');
        $list->addRow($row);
    }

    public static function provideAddRowBadID()
    {
        return [
            [['ID' => null]],
            [['ID' => '']],
            [['ID' => [1,2,3]]],
            [['Name' => 'No ID provided']],
        ];
    }

    public function testCount()
    {
        $list = new EagerLoadedList(Team::class, DataList::class);
        $this->assertSame(0, $list->count());

        $list->addRows([
            ['ID' => 1],
            ['ID' => 2],
            ['ID' => 3],
            ['ID' => 4],
        ]);
        $this->assertSame(4, $list->count());
    }

    public function testExists()
    {
        $list = new EagerLoadedList(Team::class, DataList::class);
        $this->assertFalse($list->exists());

        $list->addRows([
            ['ID' => 1],
            ['ID' => 2],
            ['ID' => 3],
            ['ID' => 4],
        ]);
        $this->assertTrue($list->exists());
    }

    #[DataProvider('provideIteration')]
    public function testIteration(string $dataListClass): void
    {
        // Get some garbage values for the manymany component so we don't get errors.
        // Real relations aren't necessary for this test.
        $manyManyComponent = [];
        if (in_array($dataListClass, [ManyManyThroughList::class, ManyManyList::class])) {
            $manyManyComponent['join'] = DataObject::class;
            $manyManyComponent['childField'] = '';
            $manyManyComponent['parentField'] = '';
            $manyManyComponent['parentClass'] = DataObject::class;
            $manyManyComponent['extraFields'] = [];
        }

        $rows = EagerLoadedListTest::getBasicRecordRows();
        $eagerloadedDataClass = Sortable::class;

        $foreignID = $dataListClass === DataList::class ? null : 9999;
        $list = new EagerLoadedList($eagerloadedDataClass, $dataListClass, $foreignID, $manyManyComponent);
        foreach ($rows as $row) {
            $list->addRow($row);
        }

        // Validate that the list has the correct records with all the right values
        $this->iterate($list, $rows, array_column($rows, 'ID'));

        // Validate a repeated iteration works correctly (this has broken for other lists in the past)
        $this->iterate($list, $rows, array_column($rows, 'ID'));
    }

    public static function provideIteration()
    {
        return [
            [DataList::class],
            [HasManyList::class],
            [ManyManyThroughList::class],
            [ManyManyList::class],
        ];
    }

    private function iterate(EagerLoadedList $list, array $rows, array $expected): void
    {
        $foundIDs = [];
        foreach ($list as $record) {
            // Assert the correct class is used for the records
            $this->assertInstanceOf($list->dataClass(), $record);
            // Get the row this record is for
            $matches = array_filter($rows, function ($row) use ($record) {
                return $row['ID'] === $record->ID;
            });
            $row = $matches[array_key_first($matches)];
            // Assert field values are correct
            foreach ($row as $field => $value) {
                $this->assertSame($value, $record->$field);
            }
            $foundIDs[] = $record->ID;
        }
        // Assert all (and only) the expected records were included in the list
        $this->assertSame($expected, $foundIDs);
    }

    #[DataProvider('provideFilter')]
    #[DataProvider('provideFilterWithSearchFilters')]
    public function testFilter(
        string $dataListClass,
        string $eagerloadedDataClass,
        array $rows,
        array $filter,
        array $expected,
    ): void {
        // Get some garbage values for the manymany component so we don't get errors.
        // Real relations aren't necessary for this test.
        $manyManyComponent = [];
        if (in_array($dataListClass, [ManyManyThroughList::class, ManyManyList::class])) {
            $manyManyComponent['join'] = DataObject::class;
            $manyManyComponent['childField'] = '';
            $manyManyComponent['parentField'] = '';
            $manyManyComponent['parentClass'] = DataObject::class;
            $manyManyComponent['extraFields'] = [];
        }

        $foreignID = $dataListClass === DataList::class ? null : 9999;
        $list = new EagerLoadedList($eagerloadedDataClass, $dataListClass, $foreignID, $manyManyComponent);
        foreach ($rows as $row) {
            $list->addRow($row);
        }
        $filteredList = $list->filter($filter);

        // Validate that the unfiltered list still has all records, and the filtered list has the expected amount
        $this->assertCount(count($rows), $list);
        $this->assertCount(count($expected), $filteredList);

        // Validate that the filtered list has the CORRECT records
        $this->iterate($list, $rows, array_column($rows, 'ID'));
    }

    public static function provideFilter(): array
    {
        $rows = EagerLoadedListTest::getBasicRecordRows();
        return [
            [
                'dataListClass' => DataList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => ['Created' => '2023-01-01 00:00:00'],
                'expected' => [2, 3],
            ],
            [
                'dataListClass' => HasManyList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => ['Created' => '2023-01-01 00:00:00'],
                'expected' => [2, 3],
            ],
            [
                'dataListClass' => ManyManyList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => ['Created' => '2023-12-01 00:00:00'],
                'expected' => [],
            ],
            [
                'dataListClass' => ManyManyThroughList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => [
                    'Created' => '2023-01-01 00:00:00',
                    'Name' => 'test obj 3',
                ],
                'expected' => [3],
            ],
            [
                'dataListClass' => ManyManyThroughList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => [
                    'Created' => '2023-01-01 00:00:00',
                    'Name' => 'not there',
                ],
                'expected' => [],
            ],
            [
                'dataListClass' => ManyManyThroughList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => [
                    'Name' => ['test obj 1', 'test obj 3', 'not there'],
                ],
                'expected' => [1, 3],
            ],
            [
                'dataListClass' => ManyManyThroughList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => [
                    'Name' => ['not there', 'also not there'],
                ],
                'expected' => [],
            ],
            [
                'dataListClass' => ManyManyThroughList::class,
                'eagerloadedDataClass' => ValidatedObject::class,
                'rows' => $rows,
                'filter' => [
                    'ID' => [1, 2],
                ],
                'expected' => [1, 2],
            ],
        ];
    }

    public static function provideFilterWithSearchFilters()
    {
        $rows = EagerLoadedListTest::getBasicRecordRows();
        $scenarios = [
            // exact match filter tests
            'exact match - negate' => [
                'filter' => ['Name:not' => 'test obj 1'],
                'expected' => [2, 3],
            ],
            'exact match - negate two different ways' => [
                'filter' => [
                    'Name:not' => 'test obj 1',
                    'Name:ExactMatch:not' => 'test obj 3',
                ],
                'expected' => [2],
            ],
            'exact match negated - nothing gets filtered out' => [
                'filter' => ['Name:not' => 'No row has this name - we should have all rows'],
                'expected' => array_column($rows, 'ID'),
            ],
            'exact match negated against null - only last item gets filtered out' => [
                'filter' => ['SomeField:not' => null],
                'expected' => [1, 2],
            ],
            'exact match negated with a few items' => [
                'filter' => [
                    'Name:not' => ['test obj 1', 'test obj 3', 'not there'],
                ],
                'expected' => [2],
            ],
            // case sensitivity checks
            'exact match case sensitive' => [
                'filter' => ['SomeField:case' => 'value'],
                'expected' => [2],
            ],
            'exact match case insensitive' => [
                'filter' => ['SomeField:nocase' => 'value'],
                'expected' => [1, 2],
            ],
            // explicit exact match
            'exact match explicit' => [
                'filter' => ['Name:ExactMatch' => 'test obj 2'],
                'expected' => [2],
            ],
            'exact match explicit with modifier' => [
                'filter' => ['Name:ExactMatch:nocase' => 'Test Obj 2'],
                'expected' => [2],
            ],
            // partialmatch filter
            'partial match' => [
                'filter' => ['SomeField:PartialMatch:case' => 'alu'],
                'expected' => [2],
            ],
            'partial match with modifier' => [
                'filter' => ['SomeField:PartialMatch:nocase' => 'alu'],
                'expected' => [1, 2],
            ],
            // greaterthan filter
            'greaterthan match' => [
                'filter' => ['ID:GreaterThan' => 2],
                'expected' => [3],
            ],
            'greaterthan match with modifier' => [
                'filter' => ['ID:GreaterThan:not' => 2],
                'expected' => [1, 2],
            ],
            // greaterthanorequal filter
            'greaterthanorequal match' => [
                'filter' => ['ID:GreaterThanOrEqual' => 2],
                'expected' => [2, 3],
            ],
            'greaterthanorequal match with modifier' => [
                'filter' => ['ID:GreaterThanOrEqual:not' => 2],
                'expected' => [1],
            ],
            // lessthan filter
            'lessthan match' => [
                'filter' => ['ID:LessThan' => 2],
                'expected' => [1],
            ],
            'lessthan match with modifier' => [
                'filter' => ['ID:LessThan:not' => 2],
                'expected' => [2, 3],
            ],
            // lessthanorequal filter
            'lessthanorequal match' => [
                'filter' => ['ID:LessThanOrEqual' => 2],
                'expected' => [1, 2],
            ],
            'lessthanorequal match with modifier' => [
                'filter' => ['ID:LessThanOrEqual:not' => 2],
                'expected' => [3],
            ],
            // various more complex filters/combinations and extra scenarios
            'complex1' => [
                'filter' => [
                    'SomeField:nocase' => 'value',
                    'Name:StartsWith' => 'test',
                ],
                'expected' => [1, 2],
            ],
            'complex2' => [
                'filter' => [
                    'ID:LessThan' => 3,
                    'ID:GreaterThan:not' => 1,
                ],
                'expected' => [1],
            ],
            'complex3' => [
                'filter' => [
                    'ID:LessThan' => 3,
                    'ID:GreaterThan' => 1,
                ],
                'expected' => [2],
            ],
        ];
        // No need to vary these between scenarios, we're just checking search filter
        // syntax works as expected.
        foreach (array_keys($scenarios) as $key) {
            array_unshift($scenarios[$key], $rows);
            array_unshift($scenarios[$key], ValidatedObject::class);
            array_unshift($scenarios[$key], DataList::class);
        }
        return $scenarios;
    }

    #[DataProvider('provideFilterAnyWithSearchFilters')]
    public function testFilterAnyWithSearchfilters(array $filter, array $expected): void
    {
        $rows = EagerLoadedListTest::getBasicRecordRows();
        $list = new EagerLoadedList(ValidatedObject::class, DataList::class);
        foreach ($rows as $row) {
            $list->addRow($row);
        }
        $filteredList = $list->filterAny($filter);

        // Validate that the unfiltered list still has all records, and the filtered list has the expected amount
        $this->assertCount(count($rows), $list);
        $this->assertCount(count($expected), $filteredList);

        // Validate that the filtered list has the CORRECT records
        $this->iterate($list, $rows, array_column($rows, 'ID'));
    }

    public static function provideFilterAnyWithSearchFilters()
    {
        return [
            // test a couple of search filters
            // don't need to be as explicit as the filter tests, just check the syntax works
            'partial match' => [
                'filter' => ['Name:PartialMatch' => 'test obj'],
                'expected' => [1, 2, 3],
            ],
            'partial match2' => [
                'filter' => ['Name:PartialMatch' => 3],
                'expected' => [3],
            ],
            'partial match with modifier' => [
                'filter' => ['SomeField:PartialMatch:nocase' => 'alu'],
                'expected' => [1, 2],
            ],
            'greaterthan match' => [
                'filter' => ['ID:GreaterThan'=> 2],
                'expected' => [3],
            ],
            'greaterthan match with modifier' => [
                'filter' => ['ID:GreaterThan:not' => 2],
                'expected' => [1, 2],
            ],
            'multiple filters match' => [
                'filter' => [
                    'SomeField:PartialMatch:case' => 'val',
                    'ID:GreaterThanOrEqual' => 2,
                ],
                'expected' => [2, 3],
            ],
            'exact match with a few items' => [
                'filter' => ['Name:ExactMatch' => ['test obj 1', 'test obj 2']],
                'expected' => [1, 2],
            ],
            'negate the above test' => [
                'filter' => ['Name:ExactMatch:not' => ['test obj 1', 'test obj 2']],
                'expected' => [3],
            ],
        ];
    }

    public static function provideExcludeWithSearchfilters()
    {
        // If it's included in the filter test, then it's excluded in the exclude test,
        // so we can just use the same scenarios and reverse the expected results.
        $rows = EagerLoadedListTest::getBasicRecordRows();
        $scenarios = EagerLoadedListTest::provideFilterWithSearchfilters();
        foreach ($scenarios as $name => $scenario) {
            $kept = [];
            $excluded = [];
            foreach ($scenario['expected'] as $id) {
                $kept[] = $id;
            }
            foreach ($rows as $row) {
                if (!in_array($row['ID'], $kept)) {
                    $excluded[] = $row['ID'];
                }
            }
            $scenarios[$name]['expected'] = $excluded;

            // Remove args we won't be using for this test
            foreach (['dataListClass', 'eagerloadedDataClass', 'rows'] as $removeFromScenario) {
                array_shift($scenarios[$name]);
            }
        }
        return $scenarios;
    }

    #[DataProvider('provideExcludeWithSearchfilters')]
    public function testExcludeWithSearchfilters(array $filter, array $expected): void
    {
        $rows = EagerLoadedListTest::getBasicRecordRows();
        $list = new EagerLoadedList(ValidatedObject::class, DataList::class);
        foreach ($rows as $row) {
            $list->addRow($row);
        }
        $filteredList = $list->exclude($filter);

        // Validate that the unfiltered list still has all records, and the filtered list has the expected amount
        $this->assertCount(count($rows), $list);
        $this->assertCount(count($expected), $filteredList);

        // Validate that the filtered list has the CORRECT records
        $this->iterate($list, $rows, array_column($rows, 'ID'));
    }

    public static function provideExcludeAnyWithSearchfilters()
    {
        // If it's included in the filterAny test, then it's excluded in the excludeAny test,
        // so we can just use the same scenarios and reverse the expected results.
        $rows = EagerLoadedListTest::getBasicRecordRows();
        $scenarios = EagerLoadedListTest::provideFilterAnyWithSearchfilters();
        foreach ($scenarios as $name => $scenario) {
            $kept = [];
            $excluded = [];
            foreach ($scenario['expected'] as $id) {
                $kept[] = $id;
            }
            foreach ($rows as $row) {
                if (!in_array($row['ID'], $kept)) {
                    $excluded[] = $row['ID'];
                }
            }
            $scenarios[$name]['expected'] = $excluded;
        }
        return $scenarios;
    }

    #[DataProvider('provideExcludeAnyWithSearchfilters')]
    public function testExcludeAnyWithSearchfilters(array $filter, array $expected): void
    {
        $rows = EagerLoadedListTest::getBasicRecordRows();
        $list = new EagerLoadedList(ValidatedObject::class, DataList::class);
        foreach ($rows as $row) {
            $list->addRow($row);
        }
        $filteredList = $list->excludeAny($filter);

        // Validate that the unfiltered list still has all records, and the filtered list has the expected amount
        $this->assertCount(count($rows), $list);
        $this->assertCount(count($expected), $filteredList);

        // Validate that the filtered list has the CORRECT records
        $this->iterate($list, $rows, array_column($rows, 'ID'));
    }

    public function testFilterByInvalidColumn()
    {
        $list = new EagerLoadedList(ValidatedObject::class, DataList::class);
        $list->addRow(['ID' => 1]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't filter by column 'NotRealField'");
        $list->filter(['NotRealField' => 'anything']);
    }

    public function testFilterByRelationColumn()
    {
        $list = new EagerLoadedList(Team::class, DataList::class);
        $list->addRow(['ID' => 1, 'CaptainID' => 1]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't filter by column 'Captain.ShirtNumber'");
        $list->filter(['Captain.ShirtNumber' => 'anything']);
    }

    #[DataProvider('provideFilterByWrongNumArgs')]
    public function testFilterByWrongNumArgs(...$args)
    {
        $list = new EagerLoadedList(ValidatedObject::class, DataList::class);
        $list->addRow(['ID' => 1]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect number of arguments passed to filter');
        $list->filter(...$args);
    }

    public static function provideFilterByWrongNumArgs()
    {
        return [
            0 => [],
            3 => [1, 2, 3],
        ];
    }

    #[DataProvider('provideLimitAndOffset')]
    public function testLimitAndOffset($length, $offset, $expectedCount, $expectException = false)
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $this->assertSame(TeamComment::get()->count(), $list->count(), 'base count should match');

        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }

        $this->assertCount($expectedCount, $list->limit($length, $offset));
        $this->assertCount(
            $expectedCount,
            $list->limit(0, 9999)->limit($length, $offset),
            'Follow up limit calls unset previous ones'
        );

        // this mirrors an assertion in the tests for DataList to ensure they work the same way
        $this->assertCount($expectedCount, $list->limit($length, $offset)->toArray());
    }

    public static function provideLimitAndOffset(): array
    {
        return [
            'no limit' => [null, 0, 3],
            'smaller limit' => [2, 0, 2],
            'greater limit' => [4, 0, 3],
            'one limit' => [1, 0, 1],
            'zero limit' => [0, 0, 0],
            'limit and offset' => [1, 1, 1],
            'false limit equivalent to 0' => [false, 0, 0],
            'offset only' => [null, 2, 1],
            'offset greater than list length' => [null, 3, 0],
            'negative length' => [-1, 0, 0, true],
            'negative offset' => [0, -1, 0, true],
        ];
    }

    public function testToNestedArray()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('ID');
        $nestedArray = $list->toNestedArray();
        $expected = [
            [
                'ClassName' => TeamComment::class,
                'Name' => 'Joe',
                'Comment' => 'This is a team comment by Joe',
                'TeamID' => $this->objFromFixture(TeamComment::class, 'comment1')->TeamID,
            ],
            [
                'ClassName' => TeamComment::class,
                'Name' => 'Bob',
                'Comment' => 'This is a team comment by Bob',
                'TeamID' => $this->objFromFixture(TeamComment::class, 'comment2')->TeamID,
            ],
            [
                'ClassName' => TeamComment::class,
                'Name' => 'Phil',
                'Comment' => 'Phil is a unique guy, and comments on team2',
                'TeamID' => $this->objFromFixture(TeamComment::class, 'comment3')->TeamID,
            ],
        ];
        $this->assertEquals(3, count($nestedArray ?? []));
        $this->assertEquals($expected[0]['Name'], $nestedArray[0]['Name']);
        $this->assertEquals($expected[1]['Comment'], $nestedArray[1]['Comment']);
        $this->assertEquals($expected[2]['TeamID'], $nestedArray[2]['TeamID']);
    }

    public function testMap()
    {
        $map = $this->getListWithRecords(TeamComment::class)->map()->toArray();
        $expected = [
            $this->idFromFixture(TeamComment::class, 'comment1') => 'Joe',
            $this->idFromFixture(TeamComment::class, 'comment2') => 'Bob',
            $this->idFromFixture(TeamComment::class, 'comment3') => 'Phil'
        ];

        $this->assertEquals($expected, $map);
        $otherMap = $this->getListWithRecords(TeamComment::class)->map('Name', 'TeamID')->toArray();
        $otherExpected = [
            'Joe' => $this->objFromFixture(TeamComment::class, 'comment1')->TeamID,
            'Bob' => $this->objFromFixture(TeamComment::class, 'comment2')->TeamID,
            'Phil' => $this->objFromFixture(TeamComment::class, 'comment3')->TeamID
        ];

        $this->assertEquals($otherExpected, $otherMap);
    }

    public function testAggregate()
    {
        // Test many_many_extraFields
        $company = $this->objFromFixture(EquipmentCompany::class, 'equipmentcompany1');
        $i = 0;
        $sum = 0;
        foreach ($company->SponsoredTeams() as $team) {
            $i++;
            $sum += $i;
            $company->SponsoredTeams()->setExtraData($team->ID, ['SponsorFee' => $i]);
        }

        $teams = $this->getListWithRecords(
            $company->SponsoredTeams(),
            ManyManyList::class,
            $company->ID,
            [EquipmentCompany::class, 'SponsoredTeams']
        );

        // try with a field that is in $db
        $this->assertEquals(7, $teams->max('NumericField'));
        $this->assertEquals(2, $teams->min('NumericField'));
        $this->assertEquals(4.5, $teams->avg('NumericField'));
        $this->assertEquals(9, $teams->sum('NumericField'));
        // try with a field from many_many_extraFields
        $this->assertEquals($i, $teams->max('SponsorFee'));
        $this->assertEquals(1, $teams->min('SponsorFee'));
        $this->assertEquals(round($sum / $i, 4), round($teams->avg('SponsorFee'), 4));
        $this->assertEquals($sum, $teams->sum('SponsorFee'));
    }

    public function testEach()
    {
        $list = $this->getListWithRecords(TeamComment::class);

        $count = 0;
        $list->each(
            function ($item) use (&$count) {
                $count++;
                $this->assertInstanceOf(TeamComment::class, $item);
            }
        );

        $this->assertEquals($count, $list->count());
    }

    public function testByID()
    {
        // We can get a single item by ID.
        $id = $this->idFromFixture(Team::class, 'team2');
        $list = $this->getListWithRecords(Team::class);
        $team = $list->byID($id);

        // byID() returns a DataObject, rather than a list
        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('Team 2', $team->Title);

        // An invalid ID returns null
        $this->assertNull($list->byID(0));
        $this->assertNull($list->byID(-1));
        $this->assertNull($list->byID(9999999));
    }

    public function testByIDs()
    {
        $knownIDs = $this->allFixtureIDs(Player::class);
        $removedID = array_pop($knownIDs);
        $expectedCount = count($knownIDs);
        $list = $this->getListWithRecords(Player::class);

        // Check we have all the players we searched for, and not the one we didn't
        $filteredList = $list->byIDs($knownIDs);
        foreach ($filteredList as $player) {
            $this->assertContains($player->ID, $knownIDs);
            $this->assertNotEquals($removedID, $player->ID);
        }
        $this->assertCount($expectedCount, $filteredList);

        // Check we don't get an extra player when we include a non-existent ID in there
        $knownIDs[] = 9999999;
        $filteredList = $list->byIDs($knownIDs);
        foreach ($filteredList as $player) {
            $this->assertContains($player->ID, $knownIDs);
            $this->assertNotEquals($removedID, $player->ID);
            $this->assertNotEquals(9999999, $player->ID);
        }
        $this->assertCount($expectedCount, $filteredList);

        // Check we don't include any records if searching against an empty list or non-existent ID
        $this->assertEmpty($list->byIDs([]));
        $this->assertEmpty($list->byIDs([9999999]));
    }

    public function testRemove()
    {
        $list = $this->getListWithRecords(Team::class);
        $obj = $this->objFromFixture(Team::class, 'team2');

        $this->assertTrue($list->hasID($obj->ID));
        $list->remove($obj);
        $this->assertFalse($list->hasID($obj->ID));
    }

    public function testCanSortBy()
    {
        // Basic check
        $team = $this->getListWithRecords(Team::class);
        $this->assertTrue($team->canSortBy('Title'));
        $this->assertFalse($team->canSortBy('SubclassDatabaseField'));
        $this->assertFalse($team->canSortBy('SomethingElse'));

        // Subclasses
        $subteam = $this->getListWithRecords(SubTeam::class);
        $this->assertTrue($subteam->canSortBy('Title'));
        $this->assertTrue($subteam->canSortBy('SubclassDatabaseField'));
        $this->assertFalse($subteam->canSortBy('SomethingElse'));
    }

    public function testCannotSortByRelation()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $this->assertFalse($list->canSortBy('Team'));
        $this->assertFalse($list->canSortBy('Team.Title'));
    }

    public function testArrayAccess()
    {
        $list = $this->getListWithRecords(Team::class)->sort('Title');

        // We can use array access to refer to single items in the EagerLoadedList, as if it were an array
        $this->assertEquals('Subteam 1', $list[0]->Title);
        $this->assertEquals('Subteam 3', $list[2]->Title);
        $this->assertEquals('Team 2', $list[4]->Title);
        $this->assertNull($list[9999]);
    }

    public function testFind()
    {
        $list = $this->getListWithRecords(Team::class);
        $record = $list->find('Title', 'Team 1');
        $this->assertEquals($this->idFromFixture(Team::class, 'team1'), $record->ID);
        // Test that you get null for a non-match
        $this->assertNull($list->find('Title', 'This team doesnt exist'));
    }

    public function testFindById()
    {
        $list = $this->getListWithRecords(Team::class);
        $record = $list->find('ID', $this->idFromFixture(Team::class, 'team1'));
        $this->assertEquals('Team 1', $record->Title);

        // Test that you can call it twice on the same list
        $record = $list->find('ID', $this->idFromFixture(Team::class, 'team2'));
        $this->assertEquals('Team 2', $record->Title);

        // Test that you get null for a non-match
        $this->assertNull($list->find('ID', 9999999));
    }

    public function testSubtract()
    {
        $comment1 = $this->objFromFixture(TeamComment::class, 'comment1');
        $subtractList = TeamComment::get()->filter('ID', $comment1->ID);
        $fullList = TeamComment::get();
        $newList = $fullList->subtract($subtractList);
        $this->assertEquals(2, $newList->Count(), 'List should only contain two objects after subtraction');
    }

    public function testSubtractBadDataclassThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $teamsComments = TeamComment::get();
        $teams = Team::get();
        $teamsComments->subtract($teams);
    }

    public function testSimpleSort()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('Name');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortOneArgumentASC()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('Name ASC');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortOneArgumentDESC()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('Name DESC');
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortOneArgumentMultipleColumns()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('TeamID ASC, Name DESC');
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortASC()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('Name', 'asc');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortDESC()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('Name', 'desc');
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortWithArraySyntaxSortASC()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort(['Name'=>'asc']);
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSortWithArraySyntaxSortDESC()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort(['Name'=>'desc']);
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortWithMultipleArraySyntaxSort()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort(['TeamID'=>'asc','Name'=>'desc']);
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSortNumeric()
    {
        $list = $this->getListWithRecords(Sortable::class);
        $list1 = $list->sort('Sort', 'ASC');
        $this->assertEquals(
            [
            -10,
            -2,
            -1,
            0,
            1,
            2,
            10
            ],
            $list1->column('Sort')
        );
    }

    public function testSortMixedCase()
    {
        $list = $this->getListWithRecords(Sortable::class);
        $list1 = $list->sort('Name', 'ASC');
        $this->assertEquals(
            [
            'Bob',
            'bonny',
            'jane',
            'John',
            'sam',
            'Steve',
            'steven'
            ],
            $list1->column('Name')
        );
    }

    public function testSortByRelation()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot sort by relations on EagerLoadedList');
        $list = $list->sort('Team.Title', 'ASC');
    }

    #[DataProvider('provideSortInvalidParameters')]
    public function testSortInvalidParameters(string $sort, string $type): void
    {
        if ($type === 'valid') {
            $this->expectNotToPerformAssertions();
        } elseif ($type === 'invalid-direction') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Invalid sort direction/');
        } elseif ($type === 'unknown-column') {
            if (!(DB::get_conn()->getConnector() instanceof MySQLiConnector)) {
                $this->markTestSkipped('Database connector is not MySQLiConnector');
            }
            $this->expectException(DatabaseException::class);
            $this->expectExceptionMessageMatches('/Unknown column/');
        } elseif ($type === 'invalid-column') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Invalid sort column/');
        } elseif ($type === 'unknown-relation') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/is not a relation on model/');
        } elseif ($type === 'nonlinear-relation') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/is not a linear relation on model/');
        } else {
            throw new \Exception("Invalid type $type");
        }
        // column('ID') is required because that triggers the actual sorting of the rows
        $this->getListWithRecords(Team::class)->sort($sort)->column('ID');
    }

    /**
     * @see DataListTest::provideRawSqlSortException()
     */
    public static function provideSortInvalidParameters(): array
    {
        return [
            ['Title', 'valid'],
            ['Title asc', 'valid'],
            ['"Title" ASC', 'valid'],
            ['Title ASC, "DatabaseField"', 'valid'],
            ['"Title", "DatabaseField" DESC', 'valid'],
            ['Title ASC, DatabaseField DESC', 'valid'],
            ['Title ASC, , DatabaseField DESC', 'invalid-column'],
            ['"Captain"."ShirtNumber"', 'invalid-column'],
            ['"Captain"."ShirtNumber" DESC', 'invalid-column'],
            ['Title BACKWARDS', 'invalid-direction'],
            ['"Strange non-existent column name"', 'invalid-column'],
            ['NonExistentColumn', 'unknown-column'],
            ['Team.NonExistentColumn', 'unknown-relation'],
            ['"DataObjectTest_Team"."NonExistentColumn" ASC', 'invalid-column'],
            ['"DataObjectTest_Team"."Title" ASC', 'invalid-column'],
            ['DataObjectTest_Team.Title', 'unknown-relation'],
            ['Title, 1 = 1', 'invalid-column'],
            ["Title,'abc' = 'abc'", 'invalid-column'],
            ['Title,Mod(ID,3)=1', 'invalid-column'],
            ['(CASE WHEN ID < 3 THEN 1 ELSE 0 END)', 'invalid-column'],
            ['Founder.Fans.Surname', 'nonlinear-relation'],
        ];
    }

    #[DataProvider('provideSortDirectionValidationTwoArgs')]
    public function testSortDirectionValidationTwoArgs(string $direction, string $type): void
    {
        if ($type === 'valid') {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Invalid sort direction/');
        }
        $this->getListWithRecords(Team::class)->sort('Title', $direction)->column('ID');
    }

    public static function provideSortDirectionValidationTwoArgs(): array
    {
        return [
            ['ASC', 'valid'],
            ['asc', 'valid'],
            ['DESC', 'valid'],
            ['desc', 'valid'],
            ['BACKWARDS', 'invalid'],
        ];
    }

    /**
     * Test passing scalar values to sort()
     */
    #[DataProvider('provideSortScalarValues')]
    public function testSortScalarValues(mixed $emtpyValue, string $type): void
    {
        $this->assertSame(['Subteam 1'], $this->getListWithRecords(Team::class)->limit(1)->column('Title'));
        $list = $this->getListWithRecords(Team::class)->sort('Title DESC');
        $this->assertSame(['Team 3'], $list->limit(1)->column('Title'));
        $this->expectException(InvalidArgumentException::class);
        if ($type === 'invalid-scalar') {
            $this->expectExceptionMessage('sort() arguments must either be a string, an array, or null');
        }
        if ($type === 'empty-scalar') {
            $this->expectExceptionMessage('Invalid sort parameter');
        }

        $list = $list->sort($emtpyValue);
        $this->assertSame(['Subteam 1'], $list->limit(1)->column('Title'));
    }

    public static function provideSortScalarValues(): array
    {
        return [
            ['', 'empty-scalar'],
            [[], 'empty-scalar'],
            [false, 'invalid-scalar'],
            [true, 'invalid-scalar'],
            [0, 'invalid-scalar'],
            [1, 'invalid-scalar'],
        ];
    }

    /**
     * Explicity tests that sort(null) will wipe any existing sort on a EagerLoadedList
     */
    public function testSortNull(): void
    {
        $order = Team::get()->column('ID');
        $list = $this->getListWithRecords(Team::class)->sort('Title DESC');
        $this->assertNotSame($order, $list->column('ID'));

        $list = $list->sort(null);
        $this->assertSame($order, $list->column('ID'));
    }

    public static function provideSortMatchesDataList()
    {
        // These will be used to make fixtures
        // We don't use a fixtures yaml file here because we want a full DataList of only
        // records with THESE values, with no other items to interfere.
        $dataSets = [
            'numbers' => [
                'field' => 'Sort',
                'values' => [null, 0, 1, 123, 2, 3],
            ],
            'numeric-strings' => [
                'field' => 'Name',
                'values' => [null, '', '0', '1', '123', '2', '3'],
            ],
            'numeric-after-strings' => [
                'field' => 'Name',
                'values' => ['test1', 'test2', 'test0', 'test123', 'test3'],
            ],
            'strings' => [
                'field' => 'Name',
                'values' => [null, '', 'abc', 'a', 'A', 'AB', '1', '0'],
            ],
        ];

        // Build the test scenario with both sort directions
        $scenarios = [];
        foreach (['ASC', 'DESC'] as $sortDir) {
            foreach ($dataSets as $data) {
                $scenarios[] = [
                    'sortDir' => $sortDir,
                    'field' => $data['field'],
                    'values' => $data['values']
                ];
            }
        }

        return $scenarios;
    }

    #[DataProvider('provideSortMatchesDataList')]
    public function testSortMatchesDataList(string $sortDir, string $field, array $values)
    {
        // Use explicit per-scenario fixtures
        Sortable::get()->removeAll();
        foreach ($values as $value) {
            $data = [$field => $value];
            if (!$field === 'Name') {
                $data['Name'] = $value;
            }
            $record = new Sortable($data);
            $record->write();
        }

        // Sort both a DataList and an EagerLoadedList by the same items
        // and validate they have the same sort order
        $dataList = Sortable::get()->sort([$field => $sortDir]);
        $eagerList = $this->getListWithRecords(Sortable::class)->sort([$field => $sortDir]);
        $this->assertSame($dataList->map('ID', $field)->toArray(), $eagerList->map('ID', $field)->toArray());
    }

    public function testCanFilterBy()
    {
        // Basic check
        $team = $this->getListWithRecords(Team::class);
        $this->assertTrue($team->canFilterBy("Title"));
        $this->assertFalse($team->canFilterBy("SomethingElse"));

        // Has one
        $this->assertTrue($team->canFilterBy("CaptainID"));

        // Subclasses
        $subteam = $this->getListWithRecords(SubTeam::class);
        $this->assertTrue($subteam->canFilterBy("Title"));
        $this->assertTrue($subteam->canFilterBy("SubclassDatabaseField"));
    }

    public function testCannotFilterByRelation()
    {
        $list = $this->getListWithRecords(Team::class);

        $this->assertFalse($list->canFilterBy('Captain.ShirtNumber'));
        $this->assertFalse($list->canFilterBy('SomethingElse.ShirtNumber'));
        $this->assertFalse($list->canFilterBy('Captain.SomethingElse'));
        $this->assertFalse($list->canFilterBy('Captain.FavouriteTeam.Captain.ShirtNumber'));

        // Has many
        $this->assertFalse($list->canFilterBy('Fans.Name'));
        $this->assertFalse($list->canFilterBy('SomethingElse.Name'));
        $this->assertFalse($list->canFilterBy('Fans.SomethingElse'));

        // Many many
        $this->assertFalse($list->canFilterBy('Players.FirstName'));
        $this->assertFalse($list->canFilterBy('SomethingElse.FirstName'));
        $this->assertFalse($list->canFilterBy('Players.SomethingElse'));
    }

    public function testAddfilter()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->addFilter(['Name' => 'Bob']);
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterAny()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filterAny('Name', 'Bob');
        $this->assertEquals(1, $list->count());
    }

    public function testFilterAnyMultipleArray()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filterAny(['Name' => 'Bob', 'Comment' => 'This is a team comment by Bob']);
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterAnyOnFilter()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filter(
            [
                'TeamID' => $this->idFromFixture(Team::class, 'team1')
            ]
        );
        $list = $list->filterAny(
            [
                'Name' => ['Phil', 'Joe'],
                'Comment' => 'This is a team comment by Bob'
            ]
        );
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals(
            'Bob',
            $list->offsetGet(0)->Name,
            'Results should include comments from Bob, matched by comment and team'
        );
        $this->assertEquals(
            'Joe',
            $list->offsetGet(1)->Name,
            'Results should include comments by Joe, matched by name and team (not by comment)'
        );

        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filter(
            [
                'TeamID' => $this->idFromFixture(Team::class, 'team1')
            ]
        );
        $list = $list->filterAny(
            [
                'Name' => ['Phil', 'Joe'],
                'Comment' => 'This is a team comment by Bob'
            ]
        );
        $list = $list->sort('Name');
        $list = $list->filter(['Name' => 'Bob']);
        $this->assertEquals(1, $list->count());
        $this->assertEquals(
            'Bob',
            $list->offsetGet(0)->Name,
            'Results should include comments from Bob, matched by name and team'
        );
    }

    public function testFilterAnyMultipleWithArrayFilter()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filterAny(['Name' => ['Bob','Phil']]);
        $this->assertEquals(2, $list->count(), 'There should be two comments');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testFilterAnyArrayInArray()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filterAny([
            'Name' => ['Bob','Phil'],
            'TeamID' => [$this->idFromFixture(Team::class, 'team1')]
        ])->sort('Name');
        $this->assertEquals(3, $list->count());
        $this->assertEquals(
            'Bob',
            $list->offsetGet(0)->Name,
            'Results should include comments from Bob, matched by name and team'
        );
        $this->assertEquals(
            'Joe',
            $list->offsetGet(1)->Name,
            'Results should include comments by Joe, matched by team (not by name)'
        );
        $this->assertEquals(
            'Phil',
            $list->offsetGet(2)->Name,
            'Results should include comments from Phil, matched by name (even if he\'s not in Team1)'
        );
    }

    public function testFilterAndExcludeById()
    {
        $id = $this->idFromFixture(SubTeam::class, 'subteam1');
        $list = $this->getListWithRecords(SubTeam::class)->filter('ID', $id);
        $this->assertEquals($id, $list->first()->ID);

        $list = $this->getListWithRecords(SubTeam::class);
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(2, count($list->exclude('ID', $id) ?? []));
    }

    public function testFilterAnyByRelation()
    {
        $list = $this->getListWithRecords(Player::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't filter by column 'Teams.Title'");
        $list = $list->filterAny(['Teams.Title' => 'Team']);
    }

    public function testFilterAggregate()
    {
        $list = $this->getListWithRecords(Team::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't filter by column 'Players.Count()'");
        $list->filter(['Players.Count()' => 2]);
    }

    public function testFilterAnyAggregate()
    {
        $list = $this->getListWithRecords(Team::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't filter by column 'Players.Count()'");
        $list->filterAny(['Players.Count()' => 2]);
    }

    public static function provideCantFilterByRelation()
    {
        return [
            'many_many' => [
                'Players.FirstName',
            ],
            'has_many' => [
                'Comments.Name',
            ],
            'has_one' => [
                'FavouriteTeam.Title',
            ],
            'non-existent relation' => [
                'MascotAnimal.Name',
            ]
        ];
    }

    #[DataProvider('provideCantFilterByRelation')]
    public function testCantFilterByRelation(string $column)
    {
        // Many to many
        $list = $this->getListWithRecords(Team::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't filter by column '$column'");
        $list->filter($column, ['Captain', 'Captain 2']);
    }

    #[DataProvider('provideFilterByNull')]
    public function testFilterByNull(string $filterMethod, array $filter, array $expected)
    {
        // Force DataObjectTest_Fan/fan5::Email to empty string
        $fan5id = $this->idFromFixture(Fan::class, 'fan5');
        DB::prepared_query("UPDATE \"DataObjectTest_Fan\" SET \"Email\" = '' WHERE \"ID\" = ?", [$fan5id]);
        $list = $this->getListWithRecords(Fan::class);

        $filteredList = $list->$filterMethod($filter);
        $this->assertListEquals($expected, $filteredList);
    }

    public static function provideFilterByNull()
    {
        return [
            'Filter by null email' => [
                'filterMethod' => 'filter',
                'filter' => ['Email' => null],
                'expected' => [
                    [
                        'Name' => 'Stephen',
                    ],
                    [
                        'Name' => 'Mitch',
                    ]
                ],
            ],
            'Filter by non-null' => [
                'filterMethod' => 'filter',
                'filter' => ['Email:not' => null],
                'expected' => [
                    [
                        'Name' => 'Damian',
                        'Email' => 'damian@thefans.com',
                    ],
                    [
                        'Name' => 'Richard',
                        'Email' => 'richie@richers.com',
                    ],
                    [
                        'Name' => 'Hamish',
                    ]
                ],
            ],
            'Filter by empty only' => [
                'filterMethod' => 'filter',
                'filter' => ['Email' => ''],
                'expected' => [
                    [
                        'Name' => 'Hamish',
                    ]
                ],
            ],
            // This should include null values, matching the behaviour in DataList
            'Non-empty only' => [
                'filterMethod' => 'filter',
                'filter' => ['Email:not' => ''],
                'expected' => [
                    [
                        'Name' => 'Damian',
                        'Email' => 'damian@thefans.com',
                    ],
                    [
                        'Name' => 'Richard',
                        'Email' => 'richie@richers.com',
                    ],
                    [
                        'Name' => 'Stephen',
                    ],
                    [
                        'Name' => 'Mitch',
                    ]
                ],
            ],
            'Filter by null or empty values' => [
                'filterMethod' => 'filter',
                'filter' => ['Email' => [null, '']],
                'expected' => [
                    [
                        'Name' => 'Stephen',
                    ],
                    [
                        'Name' => 'Mitch',
                    ],
                    [
                        'Name' => 'Hamish',
                    ]
                ],
            ],
            'Filter by many including null, empty string, and non-empty' => [
                'filterMethod' => 'filter',
                'filter' => ['Email' => [null, '', 'damian@thefans.com']],
                'expected' => [
                    [
                        'Name' => 'Damian',
                        'Email' => 'damian@thefans.com',
                    ],
                    [
                        'Name' => 'Stephen',
                    ],
                    [
                        'Name' => 'Mitch',
                    ],
                    [
                        'Name' => 'Hamish',
                    ]
                ],
            ],
            'Filter exclusion of above list' => [
                'filterMethod' => 'filter',
                'filter' => ['Email:not' => [null, '', 'damian@thefans.com']],
                'expected' => [
                    [
                        'Name' => 'Richard',
                        'Email' => 'richie@richers.com',
                    ],
                ],
            ],
            'Filter by many including empty string and non-empty 1' => [
                'filterMethod' => 'filter',
                'filter' => ['Email' => ['', 'damian@thefans.com']],
                'expected' => [
                    [
                        'Name' => 'Damian',
                        'Email' => 'damian@thefans.com',
                    ],
                    [
                        'Name' => 'Hamish',
                    ]
                ],
            ],
            'Filter by many including empty string and non-empty 2' => [
                'filterMethod' => 'filter',
                'filter' => ['Email:not' => ['', 'damian@thefans.com']],
                'expected' => [
                    [
                        'Name' => 'Richard',
                        'Email' => 'richie@richers.com',
                    ],
                    [
                        'Name' => 'Stephen',
                    ],
                    [
                        'Name' => 'Mitch',
                    ]
                ],
            ],
            'Filter by many including empty string and non-empty 3' => [
                'filterMethod' => 'filterAny',
                'filter' => [
                    'Email:not' => ['', 'damian@thefans.com'],
                    'Email' => null
                ],
                'expected' => [
                    [
                        'Name' => 'Richard',
                        'Email' => 'richie@richers.com',
                    ],
                    [
                        'Name' => 'Stephen',
                    ],
                    [
                        'Name' => 'Mitch',
                    ]
                ],
            ],
        ];
    }

    public function testFilterByCallback()
    {
        $team1ID = $this->idFromFixture(Team::class, 'team1');
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filterByCallback(
            function ($item, $list) use ($team1ID) {
                return $item->TeamID == $team1ID;
            }
        );

        $result = $list->column('Name');
        $expected = array_intersect($result ?? [], ['Joe', 'Bob']);

        $this->assertEquals(2, $list->count());
        $this->assertEquals($expected, $result, 'List should only contain comments from Team 1 (Joe and Bob)');
        $this->assertTrue($list instanceof Filterable, 'The List should be of type SS_Filterable');
    }

    public function testSimpleExclude()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude('Name', 'Bob');
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleExcludeWithMultiple()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude('Name', ['Joe', 'Phil']);
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
    }

    public function testMultipleExcludeWithMiss()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude(['Name' => 'Bob', 'Comment' => 'Does not match any comments']);
        $this->assertEquals(3, $list->count());
    }

    public function testMultipleExclude()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude(['Name' => 'Bob', 'Comment' => 'This is a team comment by Bob']);
        $this->assertEquals(2, $list->count());
    }

    /**
     * Test doesn't exclude if only matches one
     */
    public function testMultipleExcludeMultipleMatches()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude(['Name' => 'Bob', 'Comment' => 'Phil is a unique guy, and comments on team2']);
        $this->assertCount(3, $list);
    }

    /**
     * exclude only those that match both
     */
    public function testMultipleExcludeArraysMultipleMatches()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude([
            'Name' => ['Bob', 'Phil'],
            'Comment' => [
                'This is a team comment by Bob',
                'Phil is a unique guy, and comments on team2'
            ]
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    /**
     * Exclude only which matches both params
     */
    public function testMultipleExcludeArraysMultipleMatchesOneMiss()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude([
            'Name' => ['Bob', 'Phil'],
            'Comment' => [
                'Does not match any comments',
                'Phil is a unique guy, and comments on team2'
            ]
        ]);
        $list = $list->sort('Name');
        $this->assertListEquals(
            [
                ['Name' => 'Bob'],
                ['Name' => 'Joe'],
            ],
            $list
        );
    }

    /**
     * Test that if an exclude() is applied to a filter(), the filter() is still preserved.
     */
    #[DataProvider('provideExcludeOnFilter')]
    public function testExcludeOnFilter(array $filter, array $exclude, array $expected)
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->filter($filter);
        $list = $list->exclude($exclude);
        $this->assertListEquals($expected, $list->sort('Name'));
    }

    public static function provideExcludeOnFilter()
    {
        return [
            [
                'filter' => ['Comment' => 'Phil is a unique guy, and comments on team2'],
                'exclude' => ['Name' => 'Bob'],
                'expected' => [
                    ['Name' => 'Phil'],
                ],
            ],
            [
                'filter' => ['Name' => ['Phil', 'Bob']],
                'exclude' => ['Name' => ['Bob', 'Joe']],
                'expected' => [
                    ['Name' => 'Phil'],
                ],
            ],
            [
                'filter' => ['Name' => ['Phil', 'Bob']],
                'exclude' => [
                    'Name' => ['Joe', 'Phil'],
                    'Comment' => ['Matches no comments', 'Not a matching comment']
                ],
                'expected' => [
                    ['Name' => 'Bob'],
                    ['Name' => 'Phil'],
                ],
            ],
        ];
    }

    public function testExcludeWithSearchFilter()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude('Comment:PartialMatch', 'Bob');
        $this->assertListEquals([
            ['Name' => 'Joe'],
            ['Name' => 'Phil'],
        ], $list);
    }

    /**
     * Test that Bob and Phil are excluded (one match each)
     */
    public function testExcludeAny()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->excludeAny([
            'Name' => 'Bob',
            'Comment' => 'Phil is a unique guy, and comments on team2'
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    /**
     * Test that Bob and Phil are excluded by Name
     */
    public function testExcludeAnyArrays()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->excludeAny([
            'Name' => ['Bob', 'Phil'],
            'Comment' => 'No matching comments'
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    /**
     * Test that Bob is excluded by Name, Phil by comment
     */
    public function testExcludeAnyMultiArrays()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->excludeAny([
            'Name' => ['Bob', 'Fred'],
            'Comment' => ['No matching comments', 'Phil is a unique guy, and comments on team2']
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    public function testEmptyFilter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot filter Name against an empty set');
        $list = $this->getListWithRecords(TeamComment::class);
        $list->exclude('Name', []);
    }

    public function testMultipleExcludeWithMultipleThatCheersEitherTeam()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude([
            'Name' => 'Bob',
            'TeamID' => [
                $this->idFromFixture(Team::class, 'team1'),
                $this->idFromFixture(Team::class, 'team2'),
            ],
        ]);
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Phil');
        $this->assertEquals('Phil', $list->last()->Name, 'First comment should be from Phil');
    }

    public function testMultipleExcludeWithMultipleThatCheersOnNonExistingTeam()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude(['Name' => 'Bob', 'TeamID' => [3]]);
        $this->assertEquals(3, $list->count());
    }

    public function testMultipleExcludeWithNoExclusion()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude([
            'Name' => ['Bob','Joe'],
            'Comment' => 'Phil is a unique guy, and comments on team2',
        ]);
        $this->assertEquals(3, $list->count());
    }

    public function testMultipleExcludeWithTwoArray()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude([
            'Name' => ['Bob','Joe'],
            'TeamID' => [
                $this->idFromFixture(Team::class, 'team1'),
                $this->idFromFixture(Team::class, 'team2'),
            ],
        ]);
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Phil', $list->last()->Name, 'Only comment should be from Phil');
    }

    public function testMultipleExcludeWithTwoArrayOneTeam()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->exclude([
            'Name' => ['Bob', 'Phil'],
            'TeamID' => [$this->idFromFixture(Team::class, 'team1')],
        ]);
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testReverse()
    {
        $list = $this->getListWithRecords(TeamComment::class);
        $list = $list->sort('Name');
        $list = $list->reverse();

        $this->assertEquals('Bob', $list->last()->Name, 'Last comment should be from Bob');
        $this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Phil');
    }

    public function testShuffle()
    {
        // Try shuffling 3 times - it's technically possible the result of a shuffle could be
        // the exact same order as the original list.
        for ($attempts = 1; $attempts <= 3; $attempts++) {
            $list = $this->getListWithRecords(Sortable::class)->shuffle();
            $results1 = $list->column();
            $results2 = $list->column();
            // The lists should hold the same records
            $this->assertSame(count($results1), count($results2));

            $failed = false;
            try {
                // The list order should different each time we "execute" the list
                $this->assertNotSame($results1, $results2);
            } catch (ExpectationFailedException $e) {
                $failed = true;
                // Only fail the test if we've tried and failed 3 times.
                if ($attempts === 3) {
                    throw $e;
                }
            }

            // If we've passed the shuffle test, don't retry.
            if (!$failed) {
                break;
            }
        }
    }

    public function testColumn()
    {
        // sorted so postgres won't complain about the order being different
        $list = $this->getListWithRecords(RelationChildSecond::class)->sort('Title');
        $ids = [
            $this->idFromFixture(RelationChildSecond::class, 'test1'),
            $this->idFromFixture(RelationChildSecond::class, 'test2'),
            $this->idFromFixture(RelationChildSecond::class, 'test3'),
            $this->idFromFixture(RelationChildSecond::class, 'test3-duplicate'),
        ];

        // Test default
        $this->assertSame($ids, $list->column());

        // Test specific field
        $this->assertSame(['Test 1', 'Test 2', 'Test 3', 'Test 3'], $list->column('Title'));
    }

    public function testColumnUnique()
    {
        // sorted so postgres won't complain about the order being different
        $list = $this->getListWithRecords(RelationChildSecond::class)->sort('Title');
        $ids = [
            $this->idFromFixture(RelationChildSecond::class, 'test1'),
            $this->idFromFixture(RelationChildSecond::class, 'test2'),
            $this->idFromFixture(RelationChildSecond::class, 'test3'),
            $this->idFromFixture(RelationChildSecond::class, 'test3-duplicate'),
        ];

        // Test default
        $this->assertSame($ids, $list->columnUnique());

        // Test specific field
        $this->assertSame(['Test 1', 'Test 2', 'Test 3'], $list->columnUnique('Title'));
    }

    public function testColumnFailureInvalidColumn()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getListWithRecords(Category::class)->column('ObviouslyInvalidColumn');
    }

    public function testOffsetGet()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('Name');
        $this->assertEquals('Bob', $list->offsetGet(0)->Name);
        $this->assertEquals('Joe', $list->offsetGet(1)->Name);
        $this->assertEquals('Phil', $list->offsetGet(2)->Name);
        $this->assertNull($list->offsetGet(999));
    }

    public function testOffsetExists()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('Name');
        $this->assertTrue($list->offsetExists(0));
        $this->assertTrue($list->offsetExists(1));
        $this->assertTrue($list->offsetExists(2));
        $this->assertFalse($list->offsetExists(999));
    }

    public function testOffsetGetNegative()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('Name');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$offset can not be negative. -1 was provided.');
        $list->offsetGet(-1);
    }

    public function testOffsetExistsNegative()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('Name');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$key can not be negative. -1 was provided.');
        $list->offsetExists(-1);
    }

    public function testOffsetSet()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('Name');
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't alter items in an EagerLoadedList using array-access");
        $list->offsetSet(0, null);
    }

    public function testOffsetUnset()
    {
        $list = $this->getListWithRecords(TeamComment::class)->sort('Name');
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't alter items in an EagerLoadedList using array-access");
        $list->offsetUnset(0);
    }

    #[DataProvider('provideRelation')]
    public function testRelation(string $parentClass, string $relation, ?array $expected, array $eagerLoaded)
    {
        $relationList = $this->getListWithRecords($parentClass)->relation($relation);
        if ($expected === null) {
            $this->assertNull($relationList);
        } else {
            $this->assertInstanceOf(DataList::class, $relationList);
            $this->assertListEquals($expected, $relationList);
        }
    }

    #[DataProvider('provideRelation')]
    public function testRelationEagerLoaded(string $parentClass, string $relation, ?array $expected, array $eagerLoaded)
    {
        // Get an EagerLoadedList and add the relation data to it
        $list = $this->getListWithRecords($parentClass);
        foreach ($eagerLoaded as $parentFixture => $childData) {
            $parentID = $this->idFromFixture($parentClass, $parentFixture);
            if ($expected === null) {
                // has_one
                $list->addEagerLoadedData($relation, $parentID, $this->objFromFixture($childData['class'], $childData['fixture']));
            } else {
                // has_many and many_many
                $data = new EagerLoadedList($childData[0]['class'], DataList::class);
                foreach ($childData as $child) {
                    $childID = $this->idFromFixture($child['class'], $child['fixture']);
                    $data->addRow(['ID' => $childID, 'Title' => $child['Title']]);
                }
                $list->addEagerLoadedData($relation, $parentID, $data);
            }
        }

        // Test that eager loaded data is correctly fetched
        $relationList = $list->relation($relation);
        if ($expected === null) {
            $this->assertNull($relationList);
        } else {
            $this->assertInstanceOf(EagerLoadedList::class, $relationList);
            $this->assertListEquals($expected, $relationList);
        }
    }

    public static function provideRelation()
    {
        return [
            'many_many' => [
                'parentClass' => RelationChildFirst::class,
                'relation' => 'ManyNext',
                'expected' => [
                    ['Title' => 'Test 1'],
                    ['Title' => 'Test 2'],
                    ['Title' => 'Test 3'],
                ],
                'eagerLoaded' => [
                    'test1' => [
                        ['class' => RelationChildSecond::class, 'fixture' => 'test1', 'Title' => 'Test 1'],
                        ['class' => RelationChildSecond::class, 'fixture' => 'test2', 'Title' => 'Test 2'],
                    ],
                    'test2' => [
                        ['class' => RelationChildSecond::class, 'fixture' => 'test1', 'Title' => 'Test 1'],
                        ['class' => RelationChildSecond::class, 'fixture' => 'test3', 'Title' => 'Test 3'],
                    ],
                ],
            ],
            'has_many' => [
                'parentClass' => Team::class,
                'relation' => 'SubTeams',
                'expected' => [
                    ['Title' => 'Subteam 1'],
                ],
                'eagerLoaded' => [
                    'team1' => [
                        ['class' => SubTeam::class, 'fixture' => 'subteam1', 'Title' => 'Subteam 1'],
                    ],
                ],
            ],
            // calling relation() for a has_one just gives you null
            'has_one' => [
                'parentClass' => DataObjectTest\Company::class,
                'relation' => 'Owner',
                'expected' => null,
                'eagerLoaded' => [
                    'company1' => [
                        'class' => Player::class, 'fixture' => 'player1', 'Title' => 'Player 1',
                    ],
                    'company2' => [
                        'class' => Player::class, 'fixture' => 'player2', 'Title' => 'Player 2',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideCreateDataObject')]
    public function testCreateDataObject(string $dataClass, string $realClass, array $row)
    {
        $list = new EagerLoadedList($dataClass, DataList::class);

        // ID key must be present
        if (!array_key_exists('ID', $row)) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('$row must have an ID');
        }

        $obj = $list->createDataObject($row);

        // Validate the class is correct
        $this->assertSame($realClass, get_class($obj));

        // Validates all fields are available
        foreach ($row as $field => $value) {
            $this->assertSame($value, $obj->$field);
        }
    }

    public static function provideCreateDataObject()
    {
        return [
            'no ClassName' => [
                'dataClass' => Team::class,
                'realClass' => Team::class,
                'row' => [
                    'ID' => 1,
                    'Title' => 'Team 1',
                    'NumericField' => '1',
                    // Extra field that doesn't exist on that class
                    'SubclassDatabaseField' => 'this shouldnt be there',
                ],
            ],
            'subclassed ClassName' => [
                'dataClass' => Team::class,
                'realClass' => SubTeam::class,
                'row' => [
                    'ClassName' => SubTeam::class,
                    'ID' => 1,
                    'Title' => 'Team 1',
                    'SubclassDatabaseField' => 'this time it should be there',
                ],
            ],
            'RecordClassName takes precedence' => [
                'dataClass' => Team::class,
                'realClass' => SubTeam::class,
                'row' => [
                    'ClassName' => Player::class,
                    'RecordClassName' => SubTeam::class,
                    'ID' => 1,
                    'Title' => 'Team 1',
                    'SubclassDatabaseField' => 'this time it should be there',
                ],
            ],
            'No ID' => [
                'dataClass' => Team::class,
                'realClass' => Team::class,
                'row' => [
                    'Title' => 'Team 1',
                    'NumericField' => '1',
                    'SubclassDatabaseField' => 'this shouldnt be there',
                ],
            ],
        ];
    }

    public function testGetExtraFields()
    {
        // Prepare list
        $manyManyComponent = DataObject::getSchema()->manyManyComponent(Team::class, 'Players');
        $manyManyComponent['extraFields'] = DataObject::getSchema()->manyManyExtraFieldsForComponent(Team::class, 'Players');
        $list = new EagerLoadedList(Player::class, ManyManyList::class, 9999, $manyManyComponent);

        $team1 = $this->objFromFixture(Team::class, 'team1');
        $expected = DataObject::getSchema()->manyManyExtraFieldsForComponent(Team::class, 'Players');
        $this->assertSame($expected, $list->getExtraFields());
    }

    public function testGetExtraData()
    {
        // Prepare list
        $manyManyComponent = DataObject::getSchema()->manyManyComponent(Team::class, 'Players');
        $manyManyComponent['extraFields'] = DataObject::getSchema()->manyManyExtraFieldsForComponent(Team::class, 'Players');
        $list = new EagerLoadedList(Player::class, ManyManyList::class, 9999, $manyManyComponent);

        // Validate extra data
        $row1 = [
            'ID' => 1,
            'Position' => 'Captain',
        ];
        $list->addRow($row1);
        $this->assertEquals(['Position' => $row1['Position']], $list->getExtraData('Teams', $row1['ID']));
        // Also check numeric string while we're at it
        $this->assertEquals(['Position' => $row1['Position']], $list->getExtraData('Teams', (string)$row1['ID']));

        // Validate no extra data
        $row2 = [
            'ID' => '2',
        ];
        $list->addRow($row2);
        $this->assertEquals(['Position' => null], $list->getExtraData('Teams', $row2['ID']));

        // Validate no record
        $this->assertEquals([], $list->getExtraData('Teams', 99999));
    }

    public function testGetExtraDataBadID()
    {
        // Prepare list
        $manyManyComponent = DataObject::getSchema()->manyManyComponent(Team::class, 'Players');
        $manyManyComponent['extraFields'] = DataObject::getSchema()->manyManyExtraFieldsForComponent(Team::class, 'Players');
        $list = new EagerLoadedList(Player::class, ManyManyList::class, 9999, $manyManyComponent);

        // Test exception when ID not numeric
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$itemID must be an integer or numeric string');
        $list->getExtraData('Teams', 'abc');
    }

    #[DataProvider('provideGetExtraDataBadListType')]
    public function testGetExtraDataBadListType(string $listClass)
    {
        $list = new EagerLoadedList(Player::class, $listClass, 99999);
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot have extra fields on this list type');
        $list->getExtraData('Teams', 1);
    }

    public static function provideGetExtraDataBadListType()
    {
        return [
            [HasManyList::class],
            [DataList::class],
        ];
    }

    public function testDebug()
    {
        $list = Sortable::get();

        $result = $list->debug();
        $this->assertStringStartsWith('<h2>' . DataList::class . '</h2>', $result);
        $this->assertMatchesRegularExpression(
            '/<ul>\s*(<li style="list-style-type: disc; margin-left: 20px">.*?<\/li>)+\s*<\/ul>/s',
            $result
        );
        $this->assertStringEndsWith('</ul>', $result);
    }
}
