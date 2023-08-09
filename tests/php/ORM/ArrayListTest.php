<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filterable;
use stdClass;

class ArrayListTest extends SapphireTest
{

    public function testPushOperator()
    {
        $list = new ArrayList(
            [
            ['Num' => 1]
            ]
        );

        $list[] = ['Num' => 2];
        $this->assertEquals(2, count($list ?? []));
        $this->assertEquals(['Num' => 2], $list->last());

        $list[] = ['Num' => 3];
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(['Num' => 3], $list->last());
    }

    public function testArrayAccessExists()
    {
        $list = new ArrayList(
            [
            $one = new DataObject(['Title' => 'one']),
            $two = new DataObject(['Title' => 'two']),
            $three = new DataObject(['Title' => 'three'])
            ]
        );
        $this->assertEquals(count($list ?? []), 3);
        $this->assertTrue(isset($list[0]), 'First item in the set is set');
        $this->assertEquals($one, $list[0], 'First item in the set is accessible by array notation');
    }

    public function testArrayAccessUnset()
    {
        $list = new ArrayList(
            [
            $one = new DataObject(['Title' => 'one']),
            $two = new DataObject(['Title' => 'two']),
            $three = new DataObject(['Title' => 'three'])
            ]
        );
        unset($list[0]);
        $this->assertEquals(count($list ?? []), 2);
    }

    public function testArrayAccessSet()
    {
        $list = new ArrayList();
        $this->assertEquals(0, count($list ?? []));
        $list['testing!'] = $test = new DataObject(['Title' => 'I\'m testing!']);
        $this->assertEquals($test, $list['testing!'], 'Set item is accessible by the key we set it as');
    }

    public function testCount()
    {
        $list = new ArrayList();
        $this->assertEquals(0, $list->count());
        $list = new ArrayList([1, 2, 3]);
        $this->assertEquals(3, $list->count());
    }

    public function testExists()
    {
        $list = new ArrayList();
        $this->assertFalse($list->exists());
        $list = new ArrayList([1, 2, 3]);
        $this->assertTrue($list->exists());
    }

    public function testToNestedArray()
    {
        $list = new ArrayList(
            [
            ['First' => 'FirstFirst', 'Second' => 'FirstSecond'],
            (object) ['First' => 'SecondFirst', 'Second' => 'SecondSecond'],
            new ArrayListTest\TestObject('ThirdFirst', 'ThirdSecond')
            ]
        );

        $this->assertEquals(
            $list->toNestedArray(),
            [
            ['First' => 'FirstFirst', 'Second' => 'FirstSecond'],
            ['First' => 'SecondFirst', 'Second' => 'SecondSecond'],
            ['First' => 'ThirdFirst', 'Second' => 'ThirdSecond']
            ]
        );
    }

    public function testEach()
    {
        $list = new ArrayList([1, 2, 3]);

        $count = 0;
        $test = $this;

        $list->each(
            function ($item) use (&$count, $test) {
                $count++;

                $test->assertTrue(is_int($item));
            }
        );

        $this->assertEquals($list->Count(), $count);
    }

    public function limitDataProvider(): array
    {
        $all = [ ['Key' => 1], ['Key' => 2], ['Key' => 3] ];
        list($one, $two, $three) = $all;

        return [
            'smaller limit' => [2, 0, [$one, $two]],
            'limit equal to array' => [3, 0, $all],
            'limit bigger than array' => [4, 0, $all],
            'zero limit' => [0, 0, []],
            'false limit' => [0, 0, []],
            'null limit' => [null, 0, $all],

            'smaller limit with offset' => [1, 1, [$two]],
            'limit to end with offset' => [2, 1, [$two, $three]],
            'bigger limit with offset' => [3, 1, [$two, $three]],
            'offset beyond end of list' => [4, 3, []],
            'zero limit with offset' => [0, 1, []],
            'null limit with offset' => [null, 2, [$three]],

        ];
    }

    /**
     * @dataProvider limitDataProvider
     */
    public function testLimit($length, $offset, array $expected)
    {
        $data = [
            ['Key' => 1], ['Key' => 2], ['Key' => 3]
        ];
        $list = new ArrayList($data);
        $this->assertEquals(
            $list->limit($length, $offset)->toArray(),
            $expected
        );
        $this->assertEquals(
            $list->toArray(),
            $data,
            'limit is immutable and does not affect the original list'
        );
    }

    public function testLimitNegative()
    {
        $this->expectException(\InvalidArgumentException::class, 'Calling limit with a negative length throws exception');
        $list = new ArrayList(
            [
                ['Key' => 1], ['Key' => 2], ['Key' => 3]
            ]
        );
        $list->limit(-1);
    }

    public function testLimitNegativeOffset()
    {
        $this->expectException(\InvalidArgumentException::class, 'Calling limit with a negative offset throws exception');
        $list = new ArrayList(
            [
                ['Key' => 1], ['Key' => 2], ['Key' => 3]
            ]
        );
        $list->limit(1, -1);
    }

    public function testAddRemove()
    {
        $list = new ArrayList(
            [
            ['Key' => 1], ['Key' => 2]
            ]
        );

        $list->add(['Key' => 3]);
        $this->assertEquals(
            $list->toArray(),
            [
            ['Key' => 1], ['Key' => 2], ['Key' => 3]
            ]
        );

        $list->remove(['Key' => 2]);
        $this->assertEquals(
            array_values($list->toArray() ?? []),
            [
            ['Key' => 1], ['Key' => 3]
            ]
        );
    }

    public function testReplace()
    {
        $list = new ArrayList(
            [
            ['Key' => 1],
            $two = (object) ['Key' => 2],
            (object) ['Key' => 3]
            ]
        );

        $this->assertEquals(['Key' => 1], $list[0]);
        $list->replace(['Key' => 1], ['Replaced' => 1]);
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(['Replaced' => 1], $list[0]);

        $this->assertEquals($two, $list[1]);
        $list->replace($two, ['Replaced' => 2]);
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(['Replaced' => 2], $list[1]);
    }

    public function testMerge()
    {
        $list = new ArrayList(
            [
            ['Num' => 1], ['Num' => 2]
            ]
        );
        $list->merge(
            [
            ['Num' => 3], ['Num' => 4]
            ]
        );

        $this->assertEquals(4, count($list ?? []));
        $this->assertEquals(
            $list->toArray(),
            [
            ['Num' => 1], ['Num' => 2], ['Num' => 3], ['Num' => 4]
            ]
        );
    }

    public function testRemoveDuplicates()
    {
        $list = new ArrayList(
            [
            ['ID' => 1, 'Field' => 1],
            ['ID' => 2, 'Field' => 2],
            ['ID' => 3, 'Field' => 3],
            ['ID' => 4, 'Field' => 1],
            (object) ['ID' => 5, 'Field' => 2]
            ]
        );

        $this->assertEquals(5, count($list ?? []));
        $list->removeDuplicates();
        $this->assertEquals(5, count($list ?? []));

        $list->removeDuplicates('Field');
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals([1, 2, 3], $list->column('Field'));
        $this->assertEquals([1, 2, 3], $list->column('ID'));
    }

    public function testPushPop()
    {
        $list = new ArrayList(['Num' => 1]);
        $this->assertEquals(1, count($list ?? []));

        $list->push(['Num' => 2]);
        $this->assertEquals(2, count($list ?? []));
        $this->assertEquals(['Num' => 2], $list->last());

        $list->push(['Num' => 3]);
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(['Num' => 3], $list->last());

        $this->assertEquals(['Num' => 3], $list->pop());
        $this->assertEquals(2, count($list ?? []));
        $this->assertEquals(['Num' => 2], $list->last());
    }

    public function testShiftUnshift()
    {
        $list = new ArrayList(['Num' => 1]);
        $this->assertEquals(1, count($list ?? []));

        $list->unshift(['Num' => 2]);
        $this->assertEquals(2, count($list ?? []));
        $this->assertEquals(['Num' => 2], $list->first());

        $list->unshift(['Num' => 3]);
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(['Num' => 3], $list->first());

        $this->assertEquals(['Num' => 3], $list->shift());
        $this->assertEquals(2, count($list ?? []));
        $this->assertEquals(['Num' => 2], $list->first());
    }

    public function testFirstLast()
    {
        $list = new ArrayList(
            [
            ['Key' => 1], ['Key' => 2], ['Key' => 3]
            ]
        );
        $this->assertEquals($list->first(), ['Key' => 1]);
        $this->assertEquals($list->last(), ['Key' => 3]);
    }

    public function testMap()
    {
        $list = new ArrayList(
            [
            ['ID' => 1, 'Name' => 'Steve',],
            (object) ['ID' => 3, 'Name' => 'Bob'],
            ['ID' => 5, 'Name' => 'John']
            ]
        );
        $map = $list->map('ID', 'Name');
        // Items added after calling map should not be included retroactively
        $list->add(['ID' => 7, 'Name' => 'Andrew']);
        $this->assertInstanceOf('SilverStripe\\ORM\\Map', $map);
        $this->assertEquals(
            [
            1 => 'Steve',
            3 => 'Bob',
            5 => 'John'
            ],
            $map->toArray()
        );
    }

    public function testColumn()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );
        $this->assertEquals(
            $list->column('Name'),
            [
            'Steve', 'Bob', 'John'
            ]
        );
    }

    public function testSortSimpleDefaultIsSortedASC()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'bonny'],
            ]
        );

        // Unquoted name
        $list1 = $list->sort('Name');
        $this->assertEquals(
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'bonny'],
            ['Name' => 'John'],
            ['Name' => 'Steve'],
            ],
            $list1->toArray()
        );

        // Quoted name name
        $list2 = $list->sort('"Name"');
        $this->assertEquals(
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'bonny'],
            ['Name' => 'John'],
            ['Name' => 'Steve'],
            ],
            $list2->toArray()
        );

        // Array (non-associative)
        $list3 = $list->sort(['"Name"']);
        $this->assertEquals(
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'bonny'],
            ['Name' => 'John'],
            ['Name' => 'Steve'],
            ],
            $list3->toArray()
        );

        // Quoted name name with table
        $list4 = $list->sort('"Record"."Name"');
        $this->assertEquals(
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'bonny'],
            ['Name' => 'John'],
            ['Name' => 'Steve']
            ],
            $list4->toArray()
        );

        // Quoted name name with table (desc)
        $list5 = $list->sort('"Record"."Name" DESC');
        $this->assertEquals(
            [
            ['Name' => 'Steve'],
            ['Name' => 'John'],
            ['Name' => 'bonny'],
            (object) ['Name' => 'Bob']
            ],
            $list5->toArray()
        );

        // Table without quotes
        $list6 = $list->sort('Record.Name');
        $this->assertEquals(
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'bonny'],
            ['Name' => 'John'],
            ['Name' => 'Steve']
            ],
            $list6->toArray()
        );

        // Check original list isn't altered
        $this->assertEquals(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'bonny'],
            ],
            $list->toArray()
        );
    }

    public function testMixedCaseSort()
    {
        // Note: Natural sorting is not expected, so if 'bonny10' were included
        // below we would expect it to appear between bonny1 and bonny2. That's
        // undesirable though so we're not enforcing it in tests.
        $original = [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'bonny'],
            ['Name' => 'bonny1'],
            //['Name' => 'bonny10'],
            ['Name' => 'bonny2'],
        ];

        $list = new ArrayList($original);

        $expected = [
            (object) ['Name' => 'Bob'],
            ['Name' => 'bonny'],
            ['Name' => 'bonny1'],
            //['Name' => 'bonny10'],
            ['Name' => 'bonny2'],
            ['Name' => 'John'],
            ['Name' => 'Steve'],
        ];

        // Unquoted name
        $list1 = $list->sort('Name');
        $this->assertEquals($expected, $list1->toArray());

        // Quoted name name
        $list2 = $list->sort('"Name"');
        $this->assertEquals($expected, $list2->toArray());

        // Array (non-associative)
        $list3 = $list->sort(['"Name"']);
        $this->assertEquals($expected, $list3->toArray());

        // Check original list isn't altered
        $this->assertEquals($original, $list->toArray());
    }

    public function testSortSimpleASCOrder()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );

        // Sort two arguments
        $list1 = $list->sort('Name', 'ASC');
        $this->assertEquals(
            $list1->toArray(),
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'Steve']
            ]
        );

        // Sort single string
        $list2 = $list->sort('Name asc');
        $this->assertEquals(
            $list2->toArray(),
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'Steve']
            ]
        );

        // Sort quoted string
        $list3 = $list->sort('"Name" ASCENDING');
        $this->assertEquals(
            $list3->toArray(),
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'Steve']
            ]
        );

        // Sort array specifier
        $list4 = $list->sort(['Name' => 'ascending']);
        $this->assertEquals(
            $list4->toArray(),
            [
            (object) ['Name' => 'Bob'],
            ['Name' => 'John'],
            ['Name' => 'Steve']
            ]
        );

        // Check original list isn't altered
        $this->assertEquals(
            $list->toArray(),
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );
    }

    public function testSortSimpleDESCOrder()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );

        // Sort two arguments
        $list1 = $list->sort('Name', 'DESC');
        $this->assertEquals(
            $list1->toArray(),
            [
            ['Name' => 'Steve'],
            ['Name' => 'John'],
            (object) ['Name' => 'Bob']
            ]
        );

        // Sort single string
        $list2 = $list->sort('Name desc');
        $this->assertEquals(
            $list2->toArray(),
            [
            ['Name' => 'Steve'],
            ['Name' => 'John'],
            (object) ['Name' => 'Bob']
            ]
        );

        // Sort quoted string
        $list3 = $list->sort('"Name" DESCENDING');
        $this->assertEquals(
            $list3->toArray(),
            [
            ['Name' => 'Steve'],
            ['Name' => 'John'],
            (object) ['Name' => 'Bob']
            ]
        );

        // Sort array specifier
        $list4 = $list->sort(['Name' => 'descending']);
        $this->assertEquals(
            $list4->toArray(),
            [
            ['Name' => 'Steve'],
            ['Name' => 'John'],
            (object) ['Name' => 'Bob']
            ]
        );

        // Check original list isn't altered
        $this->assertEquals(
            $list->toArray(),
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );
    }

    public function testSortNumeric()
    {
        $list = new ArrayList(
            [
            ['Sort' => 0],
            ['Sort' => -1],
            ['Sort' => 1],
            ['Sort' => -2],
            ['Sort' => 2],
            ['Sort' => -10],
            ['Sort' => 10]
            ]
        );

        // Sort descending
        $list1 = $list->sort('Sort', 'DESC');
        $this->assertEquals(
            [
            ['Sort' => 10],
            ['Sort' => 2],
            ['Sort' => 1],
            ['Sort' => 0],
            ['Sort' => -1],
            ['Sort' => -2],
            ['Sort' => -10]
            ],
            $list1->toArray()
        );

        // Sort ascending
        $list1 = $list->sort('Sort', 'ASC');
        $this->assertEquals(
            [
            ['Sort' => -10],
            ['Sort' => -2],
            ['Sort' => -1],
            ['Sort' => 0],
            ['Sort' => 1],
            ['Sort' => 2],
            ['Sort' => 10]
            ],
            $list1->toArray()
        );
    }

    public function testReverse()
    {
        $list = new ArrayList(
            [
            ['Name' => 'John'],
            ['Name' => 'Bob'],
            ['Name' => 'Steve']
            ]
        );

        $list = $list->sort('Name', 'ASC');
        $list = $list->reverse();

        $this->assertEquals(
            $list->toArray(),
            [
            ['Name' => 'Steve'],
            ['Name' => 'John'],
            ['Name' => 'Bob']
            ]
        );
    }

    public function testSimpleMultiSort()
    {
        $list = new ArrayList(
            [
            (object) ['Name'=>'Object1', 'F1'=>1, 'F2'=>2, 'F3'=>3],
            (object) ['Name'=>'Object2', 'F1'=>2, 'F2'=>1, 'F3'=>4],
            (object) ['Name'=>'Object3', 'F1'=>5, 'F2'=>2, 'F3'=>2],
            ]
        );

        $list = $list->sort('F3', 'ASC');
        $this->assertEquals($list->first()->Name, 'Object3', 'Object3 should be first in the list');
        $this->assertEquals($list->last()->Name, 'Object2', 'Object2 should be last in the list');

        $list = $list->sort('F3', 'DESC');
        $this->assertEquals($list->first()->Name, 'Object2', 'Object2 should be first in the list');
        $this->assertEquals($list->last()->Name, 'Object3', 'Object3 should be last in the list');
    }

    public function testMultiSort()
    {
        $list = new ArrayList(
            [
            (object) ['ID'=>3, 'Name'=>'Bert', 'Importance'=>1],
            (object) ['ID'=>1, 'Name'=>'Aron', 'Importance'=>2],
            (object) ['ID'=>2, 'Name'=>'Aron', 'Importance'=>1],
            ]
        );

        $list = $list->sort(['Name'=>'ASC', 'Importance'=>'ASC']);
        $this->assertEquals($list->first()->ID, 2, 'Aron.2 should be first in the list');
        $this->assertEquals($list->last()->ID, 3, 'Bert.3 should be last in the list');

        $list = $list->sort(['Name'=>'ASC', 'Importance'=>'DESC']);
        $this->assertEquals($list->first()->ID, 1, 'Aron.2 should be first in the list');
        $this->assertEquals($list->last()->ID, 3, 'Bert.3 should be last in the list');
    }

    /**
     * Check that we don't cause recursion errors with array_multisort() and circular dependencies
     */
    public function testSortWithCircularDependencies()
    {
        $itemA = new stdClass;
        $childA = new stdClass;
        $itemA->child = $childA;
        $childA->parent = $itemA;
        $itemA->Sort = 1;

        $itemB = new stdClass;
        $childB = new stdClass;
        $itemB->child = $childB;
        $childB->parent = $itemB;
        $itemB->Sort = 1;

        $items = new ArrayList;
        $items->add($itemA);
        $items->add($itemB);

        // This call will trigger a fatal error if there are issues with circular dependencies
        $items->sort('Sort');
        $this->assertTrue(true, 'Sort with circular dependencies does not trigger an error.');
    }

    private function getFilterObjects()
    {
        return [
            [
                'ID' => 1,
                'Name' => 'Steve',
                'Age' => 21,
                'Title' => 'First Object',
                'NoCase' => 'CaSe SeNsItIvE',
                'CaseSensitive' => 'Case Sensitive',
                'StartsWithTest' => 'Test Value',
                'GreaterThan100' => 300,
                'LessThan100' => 50,
                'SomeField' => 'Some Value',
            ],
            [
                'ID' => 2,
                'Name' => 'Steve',
                'Age' => 18,
                'Title' => 'Second Object',
                'NoCase' => 'case sensitive',
                'CaseSensitive' => 'case sensitive',
                'StartsWithTest' => 'Not Starts With Test',
                'GreaterThan100' => 101,
                'LessThan100' => 99,
                'SomeField' => 'Another Value',
            ],
            (object) [
                'ID' => 3,
                'Name' => 'Steve',
                'Age' => 43,
                'Title' => 'Third Object',
                'NoCase' => null,
                'CaseSensitive' => '',
                'StartsWithTest' => 'Does not start with test',
                'GreaterThan100' => 99,
                'LessThan100' => 99,
                'SomeField' => 'Some Value',
            ],
            [
                'ID' => 4,
                'Name' => 'Clair',
                'Age' => 21,
                'Title' => 'Fourth Object',
                'StartsWithTest' => 'test value, but lower case',
                'GreaterThan100' => 100,
                'LessThan100' => 100,
                'SomeField' => 'some value',
            ],
            // This one importantly doesn't have a lot of fields that the other items have
            [
                'ID' => 5,
                'Name' => 'Clair',
                'Age' => 52,
                'Title' => '',
            ],
        ];
    }

    public function provideFind()
    {
        $objects = $this->getFilterObjects();
        return [
            'exact match with object' => [
                'objects' => $objects,
                'find' => ['Title', 'Third Object'],
                'expected' => $objects[2],
            ],
            'exact match with array' => [
                'objects' => $objects,
                'find' => ['Title', 'First Object'],
                'expected' => $objects[0],
            ],
            'exact match null' => [
                'objects' => $objects,
                'find' => ['NoCase', null],
                'expected' => $objects[2],
            ],
            'exact match case sensitive' => [
                'objects' => $objects,
                'find' => ['NoCase', 'case sensitive'],
                'expected' => $objects[1],
            ],
            'exact match not case sensitive' => [
                'objects' => $objects,
                'find' => ['NoCase:nocase', 'case sensitive'],
                'expected' => $objects[0],
            ],
            'startswith match' => [
                'objects' => $objects,
                'find' => ['StartsWithTest:StartsWith', 'test'],
                'expected' => $objects[3],
            ],
            'startswith match no case' => [
                'objects' => $objects,
                'find' => ['StartsWithTest:StartsWith:nocase', 'test'],
                'expected' => $objects[0],
            ],
            'startswith match negated' => [
                'objects' => $objects,
                'find' => ['StartsWithTest:StartsWith:not', 'Test'],
                'expected' => $objects[1],
            ],
            'lessthan match' => [
                'objects' => $objects,
                'find' => ['GreaterThan100:LessThan', '100'],
                'expected' => $objects[2],
            ],
            'nomatch exact' => [
                'objects' => $objects,
                'find' => ['Title', 'No results'],
                'expected' => null,
            ],
            'nomatch exact null vs empty string' => [
                'objects' => $objects,
                'find' => ['Title', null],
                'expected' => null,
            ],
            'nomatch greaterthan' => [
                'objects' => $objects,
                'find' => ['LessThan100:GreaterThan', 1000],
                'expected' => null,
            ],
            'nomatch lessthan' => [
                'objects' => $objects,
                'find' => ['LessThan100:LessThan:not', 1000],
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider provideFind
     */
    public function testFind(array $objects, array $find, object|array|null $expected)
    {
        $list = new ArrayList($objects);
        $this->assertEquals($expected, $list->find($find[0], $find[1]));
    }

    public function provideFilter()
    {
        // Note that search filter tests here are to test syntax and to ensure all supported search filters
        // work with arraylist - but we don't need to test every possible edge case here,
        // we can rely on individual searchfilter unit tests for most edge cases
        $objects = $this->getFilterObjects();
        return [
            // testing basic and advanced filter scenarios - ignore SearchFilters for now
            'exact match' => [
                'objects' => $objects,
                'filter' => ['Title', 'Third Object'],
                'expected' => [$objects[2]],
                'filterWithArgs' => true,
            ],
            'exact match multiple' => [
                'objects' => $objects,
                'filter' => ['Title', ['First Object', 'Third Object']],
                'expected' => [$objects[0], $objects[2]],
                'filterWithArgs' => true,
            ],
            'exact match multiple, array syntax' => [
                'objects' => $objects,
                'filter' => ['Title' => ['First Object', 'Third Object']],
                'expected' => [$objects[0], $objects[2]],
            ],
            'exact match against several fields' => [
                'objects' => $objects,
                'filter' => [
                    'SomeField' => 'Some Value',
                    'Title' => 'Third Object',
                ],
                'expected' => [$objects[2]],
            ],
            'exact match against several fields with multiple values' => [
                'objects' => $objects,
                'filter' => [
                    'Name' => 'Steve',
                    'Age' => [21, 43],
                ],
                'expected' => [$objects[0], $objects[2]],
            ],
            'exact match against several fields with multiple values advanced' => [
                'objects' => $objects,
                'filter' => [
                    'Name' => ['Steve', 'Clair'],
                    'Age' => [21, 43],
                ],
                'expected' => [$objects[0], $objects[2], $objects[3]],
            ],
            'no match multiple' => [
                'objects' => $objects,
                'filter' => ['Title' => ['No match', 'Another miss']],
                'expected' => [],
            ],
            'no match against several fields' => [
                'objects' => $objects,
                'filter' => [
                    'SomeField' => 'Some Value',
                    'Title' => 'No Object',
                ],
                'expected' => [],
            ],
            // exact match filter tests
            'exact match - negate' => [
                'objects' => $objects,
                'filter' => ['Title:not' => 'First Object'],
                'expected' => [$objects[1], $objects[2], $objects[3], $objects[4]],
            ],
            'exact match - negate two different ways' => [
                'objects' => $objects,
                'filter' => [
                    'Title:not' => 'First Object',
                    'Title:ExactMatch:not' => 'Third Object',
                ],
                'expected' => [$objects[1], $objects[3], $objects[4]],
            ],
            'exact match negated - nothing gets filtered out' => [
                'objects' => $objects,
                'filter' => ['Title:not' => 'No object has this title - we should have all objects'],
                'expected' => $objects,
            ],
            'exact match negated against null - only last item gets filtered out' => [
                'objects' => $objects,
                'filter' => ['SomeField:not' => null],
                'expected' => [$objects[0], $objects[1], $objects[2], $objects[3]],
            ],
            // case sensitivity checks
            'exact match case sensitive' => [
                'objects' => $objects,
                'filter' => ['NoCase' => 'case sensitive'],
                'expected' => [$objects[1]],
            ],
            'exact match case insensitive' => [
                'objects' => $objects,
                'filter' => ['NoCase:nocase' => 'case sensitive'],
                'expected' => [$objects[0], $objects[1]],
            ],
            'exact match mixed case filters' => [
                'objects' => $objects,
                'filter' => [
                    'NoCase:nocase' => 'case sensitive',
                    'CaseSensitive' => 'case sensitive',
                ],
                'expected' => [$objects[1]],
            ],
            // explicit exact match
            'exact match explicit' => [
                'objects' => $objects,
                'filter' => ['Title:ExactMatch', 'Third Object'],
                'expected' => [$objects[2]],
                'filterWithArgs' => true,
            ],
            'exact match explicit with modifier' => [
                'objects' => $objects,
                'filter' => ['Title:ExactMatch:nocase' => 'third object'],
                'expected' => [$objects[2]],
            ],
            // partialmatch filter
            'partial match' => [
                'objects' => $objects,
                'filter' => ['StartsWithTest:PartialMatch' => 'start'],
                'expected' => [$objects[2]],
            ],
            'partial match with modifier' => [
                'objects' => $objects,
                'filter' => ['StartsWithTest:PartialMatch:nocase' => 'start'],
                'expected' => [$objects[1], $objects[2]],
            ],
            // greaterthan filter
            'greaterthan match' => [
                'objects' => $objects,
                'filter' => ['GreaterThan100:GreaterThan' => 100],
                'expected' => [$objects[0], $objects[1]],
            ],
            'greaterthan match with modifier' => [
                'objects' => $objects,
                'filter' => ['GreaterThan100:GreaterThan:not' => 100],
                'expected' => [$objects[2], $objects[3], $objects[4]],
            ],
            // greaterthanorequal filter
            'greaterthanorequal match' => [
                'objects' => $objects,
                'filter' => ['GreaterThan100:GreaterThanOrEqual' => 100],
                'expected' => [$objects[0], $objects[1], $objects[3]],
            ],
            'greaterthanorequal match with modifier' => [
                'objects' => $objects,
                'filter' => ['GreaterThan100:GreaterThanOrEqual:not' => 100],
                'expected' => [$objects[2], $objects[4]],
            ],
            // lessthan filter
            'lessthan match' => [
                'objects' => $objects,
                'filter' => ['LessThan100:LessThan' => 100],
                'expected' => [$objects[0], $objects[1], $objects[2], $objects[4]],
            ],
            'lessthan match with modifier' => [
                'objects' => $objects,
                'filter' => ['LessThan100:LessThan:not' => 100],
                'expected' => [$objects[3]],
            ],
            // lessthanorequal filter
            'lessthanorequal match' => [
                'objects' => $objects,
                'filter' => ['LessThan100:LessThanOrEqual' => 99],
                'expected' => [$objects[0], $objects[1], $objects[2], $objects[4]],
            ],
            'lessthanorequal match with modifier' => [
                'objects' => $objects,
                'filter' => ['LessThan100:LessThanOrEqual:not' => 99],
                'expected' => [$objects[3]],
            ],
            // various more complex filters/combinations and extra scenarios
            'complex1' => [
                'objects' => $objects,
                'filter' => [
                    'NoCase:nocase' => 'CASE SENSITIVE',
                    'StartsWithTest:StartsWith' => 'Not',
                ],
                'expected' => [$objects[1]],
            ],
            'complex2' => [
                'objects' => $objects,
                'filter' => [
                    'NoCase:case' => 'CASE SENSITIVE',
                    'StartsWithTest:StartsWith' => 'Not',
                ],
                'expected' => [],
            ],
            'complex3' => [
                'objects' => $objects,
                'filter' => [
                    'LessThan100:LessThan' => 100,
                    'GreaterThan100:GreaterThan:not' => 100,
                ],
                'expected' => [$objects[2], $objects[4]],
            ],
            'complex4' => [
                'objects' => $objects,
                'filter' => [
                    'LessThan100:LessThan' => 1,
                    'GreaterThan100:GreaterThan' => 100,
                ],
                'expected' => [],
            ],
        ];
    }

    /**
     * @dataProvider provideFilter
     */
    public function testFilter(array $objects, array $filter, array $expected, bool $filterWithArgs = false)
    {
        $list = new ArrayList($objects);
        if ($filterWithArgs) {
            $list = $list->filter($filter[0], $filter[1]);
        } else {
            $list = $list->filter($filter);
        }
        $this->assertEquals($expected, $list->toArray());
    }

    public function provideFilterAny()
    {
        $objects = $this->getFilterObjects();
        return [
            // testing basic and advanced filter scenarios - ignore SearchFilters for now
            'exact match' => [
                'objects' => $objects,
                'filter' => ['Title', 'Third Object'],
                'expected' => [$objects[2]],
                'filterWithArgs' => true,
            ],
            'exact match multiple' => [
                'objects' => $objects,
                'filter' => ['Title', ['First Object', 'Third Object']],
                'expected' => [$objects[0], $objects[2]],
                'filterWithArgs' => true,
            ],
            'exact match multiple, array syntax' => [
                'objects' => $objects,
                'filter' => ['Title' => ['First Object', 'Third Object']],
                'expected' => [$objects[0], $objects[2]],
            ],
            'exact match multiple, one wouldnt match' => [
                'objects' => $objects,
                'filter' => ['Title' => ['First Object', 'Missing Object']],
                'expected' => [$objects[0]],
            ],
            'exact match against several fields - with crossover' => [
                'objects' => $objects,
                'filter' => [
                    'SomeField' => 'Some Value',
                    'Title' => 'Third Object',
                ],
                'expected' => [$objects[0], $objects[2]],
            ],
            'exact match against several fields - without crossover' => [
                'objects' => $objects,
                'filter' => [
                    'SomeField' => 'Some Value',
                    'Title' => 'Fourth Object',
                ],
                'expected' => [$objects[0], $objects[2], $objects[3]],
            ],
            'exact match multiple fields, multiple values' => [
                'objects' => $objects,
                'filter' => [
                    'Title' => 'First Object',
                    'Name' => ['Steve', 'Bob']
                ],
                'expected' => [$objects[0], $objects[1], $objects[2]],
            ],
            'exact match multiple fields, all multiple values' => [
                'objects' => $objects,
                'filter' => [
                    'Title' => ['First Object', 'Fourth Object'],
                    'Name' => ['Steve', 'Bob']
                ],
                'expected' => [$objects[0], $objects[1], $objects[2], $objects[3]],
            ],
            'no match' => [
                'objects' => $objects,
                'filter' => [
                    'Title' => 'Ninth Object',
                    'Name' => ['Janet', 'Bob']
                ],
                'expected' => [],
            ],
            // test a couple of search filters
            // don't need to be as explicit as the filter tests, just check the syntax works
            'partial match' => [
                'objects' => $objects,
                'filter' => ['StartsWithTest:PartialMatch' => 'start'],
                'expected' => [$objects[2]],
            ],
            'partial match with modifier' => [
                'objects' => $objects,
                'filter' => ['StartsWithTest:PartialMatch:nocase' => 'start'],
                'expected' => [$objects[1], $objects[2]],
            ],
            'greaterthan match' => [
                'objects' => $objects,
                'filter' => ['GreaterThan100:GreaterThan' => 100],
                'expected' => [$objects[0], $objects[1]],
            ],
            'greaterthan match with modifier' => [
                'objects' => $objects,
                'filter' => ['GreaterThan100:GreaterThan:not' => 100],
                'expected' => [$objects[2], $objects[3], $objects[4]],
            ],
        ];
    }

    /**
     * @dataProvider provideFilterAny
     */
    public function testFilterAny(array $objects, array $filter, array $expected, bool $filterWithArgs = false)
    {
        $list = new ArrayList($objects);
        if ($filterWithArgs) {
            $list = $list->filterAny($filter[0], $filter[1]);
        } else {
            $list = $list->filterAny($filter);
        }
        $this->assertEquals($expected, $list->toArray());
    }

    public function provideExclude()
    {
        // If it's included in the filter test, then it's excluded in the exclude test,
        // so we can just use the same scenarios and reverse the expected results.
        $objects = $this->getFilterObjects();
        $scenarios = $this->provideFilter();
        foreach ($scenarios as $name => $scenario) {
            $kept = [];
            $excluded = [];
            foreach ($scenario['expected'] as $object) {
                $objectAsArray = (array)$object;
                $kept[] = $objectAsArray['ID'];
            }
            foreach ($objects as $object) {
                $objectAsArray = (array)$object;
                if (!in_array($objectAsArray['ID'], $kept)) {
                    $excluded[] = $object;
                }
            }
            $scenarios[$name]['expected'] = $excluded;
        }
        return $scenarios;
    }

    /**
     * @dataProvider provideExclude
     */
    public function testExclude(array $objects, array $exclude, array $expected, bool $filterWithArgs = false)
    {
        $list = new ArrayList($objects);
        if ($filterWithArgs) {
            $list = $list->exclude($exclude[0], $exclude[1]);
        } else {
            $list = $list->exclude($exclude);
        }
        $this->assertEquals($expected, $list->toArray());
    }

    public function provideExcludeAny()
    {
        // If it's included in the filterAny test, then it's excluded in the excludeAny test,
        // so we can just use the same scenarios and reverse the expected results.
        $objects = $this->getFilterObjects();
        $scenarios = $this->provideFilterAny();
        foreach ($scenarios as $name => $scenario) {
            $kept = [];
            $excluded = [];
            foreach ($scenario['expected'] as $object) {
                $objectAsArray = (array)$object;
                $kept[] = $objectAsArray['ID'];
            }
            foreach ($objects as $object) {
                $objectAsArray = (array)$object;
                if (!in_array($objectAsArray['ID'], $kept)) {
                    $excluded[] = $object;
                }
            }
            $scenarios[$name]['expected'] = $excluded;
        }
        return $scenarios;
    }

    /**
     * @dataProvider provideExcludeAny
     */
    public function testExcludeAny(array $objects, array $exclude, array $expected, bool $filterWithArgs = false)
    {
        $list = new ArrayList($objects);
        if ($filterWithArgs) {
            $list = $list->excludeAny($exclude[0], $exclude[1]);
        } else {
            $list = $list->excludeAny($exclude);
        }
        $this->assertEquals($expected, $list->toArray());
    }

    /**
     * $list = $list->filterByCallback(function($item, $list) { return $item->Age == 21; })
     */
    public function testFilterByCallback()
    {
        $list = new ArrayList(
            [
            $steve = ['Name' => 'Steve', 'ID' => 1, 'Age' => 21],
            ['Name' => 'Bob', 'ID' => 2, 'Age' => 18],
            $clair = ['Name' => 'Clair', 'ID' => 2, 'Age' => 21],
            ['Name' => 'Oscar', 'ID' => 2, 'Age' => 52],
            ['Name' => 'Mike', 'ID' => 3, 'Age' => 43]
            ]
        );

        $list = $list->filterByCallback(
            function ($item, $list) {
                return $item->Age == 21;
            }
        );

        $this->assertEquals(2, $list->count());
        $this->assertEquals($steve, $list[0]->toMap(), 'List should only contain Steve and Clair');
        $this->assertEquals($clair, $list[1]->toMap(), 'List should only contain Steve and Clair');
        $this->assertTrue($list instanceof Filterable, 'The List should be of type SS_Filterable');
    }

    public function testCanFilterBy()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );

        $this->assertTrue($list->canFilterBy('Name'));
        $this->assertFalse($list->canFilterBy('Age'));
    }

    public function testCanFilterByEmpty()
    {
        $list = new ArrayList();

        $this->assertFalse($list->canFilterBy('Name'));
        $this->assertFalse($list->canFilterBy('Age'));
    }

    public function testByID()
    {
        $list = new ArrayList(
            [
            ['ID' => 1, 'Name' => 'Steve'],
            ['ID' => 2, 'Name' => 'Bob'],
            ['ID' => 3, 'Name' => 'John']
            ]
        );

        $element = $list->byID(1);
        $this->assertEquals($element['Name'], 'Steve');

        $element = $list->byID(2);
        $this->assertEquals($element['Name'], 'Bob');

        $element = $list->byID(4);
        $this->assertNull($element);
    }

    public function testByIDs()
    {
        $list = new ArrayList(
            [
            ['ID' => 1, 'Name' => 'Steve'],
            ['ID' => 2, 'Name' => 'Bob'],
            ['ID' => 3, 'Name' => 'John']
            ]
        );
        $knownIDs = $list->column('ID');
        $removedID = array_pop($knownIDs);
        $filteredItems = $list->byIDs($knownIDs);
        foreach ($filteredItems as $item) {
            $this->assertContains($item->ID, $knownIDs);
            $this->assertNotEquals($removedID, $item->ID);
        }
    }

    public function testByIDEmpty()
    {
        $list = new ArrayList();

        $element = $list->byID(1);
        $this->assertNull($element);
    }

    public function testDataClass()
    {
        $list = new ArrayList([
            new DataObject(['Title' => 'one']),
        ]);
        $this->assertEquals(DataObject::class, $list->dataClass());
        $list->pop();
        $this->assertNull($list->dataClass());
        $list->setDataClass(DataObject::class);
        $this->assertEquals(DataObject::class, $list->dataClass());
    }

    public function testShuffle()
    {
        $upperLimit = 50;

        $list = new ArrayList(range(1, $upperLimit));

        $list->shuffle();

        for ($i = 1; $i <= $upperLimit; $i++) {
            $this->assertContains($i, $list);
        }

        $this->assertNotEquals(range(1, $upperLimit), $list->toArray());
    }

    public function testOffsetSet()
    {
        $list = new ArrayList(['first value', 'second value']);
        $this->assertSame(2, $list->count());
        $list->offsetSet(0, 'new value');
        $this->assertSame(2, $list->count());
        $this->assertSame('new value', $list->offsetGet(0));
        $this->assertSame('second value', $list->offsetGet(1));
    }
}
