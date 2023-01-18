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

    public function testFind()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );
        $this->assertEquals(
            $list->find('Name', 'Bob'),
            (object) [
            'Name' => 'Bob'
            ]
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

    /**
     * $list->filter('Name', 'bob'); // only bob in the list
     */
    public function testSimpleFilter()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );
        $list = $list->filter('Name', 'Bob');
        $this->assertEquals([(object)['Name'=>'Bob']], $list->toArray(), 'List should only contain Bob');
    }

    /**
     * $list->filter('Name', ['Steve', 'John']; // Steve and John in list
     */
    public function testSimpleFilterWithMultiple()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            (object) ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );

        $expected = [
            ['Name' => 'Steve'],
            ['Name' => 'John']
        ];
        $list = $list->filter('Name', ['Steve','John']);
        $this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and John');
    }

    /**
     * $list->filter('Name', ['Steve', 'John']; // negative version
     */
    public function testSimpleFilterWithMultipleNoMatch()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve', 'ID' => 1],
            (object) ['Name' => 'Steve', 'ID' => 2],
            ['Name' => 'John', 'ID' => 2]
            ]
        );
        $list = $list->filter(['Name'=>'Clair']);
        $this->assertEquals([], $list->toArray(), 'List should be empty');
    }

    /**
     * $list->filter(['Name'=>'bob, 'Age'=>21]); // bob with the Age 21 in list
     */
    public function testMultipleFilter()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve', 'ID' => 1],
            (object) ['Name' => 'Steve', 'ID' => 2],
            ['Name' => 'John', 'ID' => 2]
            ]
        );
        $list = $list->filter(['Name'=>'Steve', 'ID'=>2]);
        $this->assertEquals(
            [(object)['Name'=>'Steve', 'ID'=>2]],
            $list->toArray(),
            'List should only contain object Steve'
        );
    }

    /**
     * $list->filter(['Name'=>'bob, 'Age'=>21]); // negative version
     */
    public function testMultipleFilterNoMatch()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve', 'ID' => 1],
            (object) ['Name' => 'Steve', 'ID' => 2],
            ['Name' => 'John', 'ID' => 2]
            ]
        );
        $list = $list->filter(['Name'=>'Steve', 'ID'=>4]);
        $this->assertEquals([], $list->toArray(), 'List should be empty');
    }

    /**
     * $list->filter(['Name'=>'Steve', 'Age'=>[21, 43]]); // Steve with the Age 21 or 43
     */
    public function testMultipleWithArrayFilter()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
            ['Name' => 'Steve', 'ID' => 2, 'Age'=>18],
            ['Name' => 'Clair', 'ID' => 2, 'Age'=>21],
            ['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
            ]
        );

        $list = $list->filter(['Name'=>'Steve','Age'=>[21, 43]]);

        $expected = [
            ['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
            ['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
        ];
        $this->assertEquals(2, $list->count());
        $this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and Steve');
    }

    /**
     * $list->filter(['Name'=>['aziz','bob'], 'Age'=>[21, 43]]);
     */
    public function testMultipleWithArrayFilterAdvanced()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
            ['Name' => 'Steve', 'ID' => 2, 'Age'=>18],
            ['Name' => 'Clair', 'ID' => 2, 'Age'=>21],
            ['Name' => 'Clair', 'ID' => 2, 'Age'=>52],
            ['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
            ]
        );

        $list = $list->filter(['Name'=>['Steve','Clair'],'Age'=>[21, 43]]);

        $expected = [
            ['Name' => 'Steve', 'ID' => 1, 'Age'=>21],
            ['Name' => 'Clair', 'ID' => 2, 'Age'=>21],
            ['Name' => 'Steve', 'ID' => 3, 'Age'=>43]
        ];

        $this->assertEquals(3, $list->count());
        $this->assertEquals($expected, $list->toArray(), 'List should only contain Steve and Steve and Clair');
    }

    public function testFilterAny()
    {

        $list = new ArrayList(
            [
            $steve = ['Name' => 'Steve', 'ID' => 1, 'Age' => 21],
            $bob = ['Name' => 'Bob', 'ID' => 2, 'Age' => 18],
            $clair = ['Name' => 'Clair', 'ID' => 3, 'Age' => 21],
            $phil = ['Name' => 'Phil', 'ID' => 4, 'Age' => 21],
            $oscar = ['Name' => 'Oscar', 'ID' => 5, 'Age' => 52],
            $mike = ['Name' => 'Mike', 'ID' => 6, 'Age' => 43],
            ]
        );

        // only bob in the list
        //$list = $list->filterAny('Name', 'bob');
        $filteredList = $list->filterAny('Name', 'Bob')->toArray();
        $this->assertCount(1, $filteredList);
        $this->assertContains($bob, $filteredList);

        // azis or bob in the list
        //$list = $list->filterAny('Name', ['aziz', 'bob']);
        $filteredList = $list->filterAny('Name', ['Aziz', 'Bob'])->toArray();
        $this->assertCount(1, $filteredList);
        $this->assertContains($bob, $filteredList);

        $filteredList = $list->filterAny('Name', ['Steve', 'Bob'])->toArray();
        $this->assertCount(2, $filteredList);
        $this->assertContains($steve, $filteredList);
        $this->assertContains($bob, $filteredList);

        // bob or anyone aged 21 in the list
        //$list = $list->filterAny(['Name'=>'bob, 'Age'=>21]);
        $filteredList = $list->filterAny(['Name' => 'Bob', 'Age' => 21])->toArray();
        $this->assertCount(4, $filteredList);
        $this->assertContains($bob, $filteredList);
        $this->assertContains($steve, $filteredList);
        $this->assertContains($clair, $filteredList);
        $this->assertContains($phil, $filteredList);

        // bob or anyone aged 21 or 43 in the list
        // $list = $list->filterAny(['Name'=>'bob, 'Age'=>[21, 43]]);
        $filteredList = $list->filterAny(['Name' => 'Bob', 'Age' => [21, 43]])->toArray();
        $this->assertCount(5, $filteredList);
        $this->assertContains($bob, $filteredList);
        $this->assertContains($steve, $filteredList);
        $this->assertContains($clair, $filteredList);
        $this->assertContains($mike, $filteredList);
        $this->assertContains($phil, $filteredList);

        // all bobs, phils or anyone aged 21 or 43 in the list
        //$list = $list->filterAny(['Name'=>['bob','phil'], 'Age'=>[21, 43]]);
        $filteredList = $list->filterAny(['Name' => ['Bob', 'Phil'], 'Age' => [21, 43]])->toArray();
        $this->assertCount(5, $filteredList);
        $this->assertContains($bob, $filteredList);
        $this->assertContains($steve, $filteredList);
        $this->assertContains($clair, $filteredList);
        $this->assertContains($mike, $filteredList);
        $this->assertContains($phil, $filteredList);

        $filteredList = $list->filterAny(['Name' => ['Bob', 'Nobody'], 'Age' => [21, 43]])->toArray();
        $this->assertCount(5, $filteredList);
        $this->assertContains($bob, $filteredList);
        $this->assertContains($steve, $filteredList);
        $this->assertContains($clair, $filteredList);
        $this->assertContains($mike, $filteredList);
        $this->assertContains($phil, $filteredList);
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

    /**
     * $list->exclude('Name', 'bob'); // exclude bob from list
     */
    public function testSimpleExclude()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );

        $list = $list->exclude('Name', 'Bob');
        $expected = [
            ['Name' => 'Steve'],
            ['Name' => 'John']
        ];
        $this->assertEquals(2, $list->count());
        $this->assertEquals($expected, $list->toArray(), 'List should not contain Bob');
    }

    /**
     * $list->exclude('Name', 'bob'); // No exclusion version
     */
    public function testSimpleExcludeNoMatch()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );

        $list = $list->exclude('Name', 'Clair');
        $expected = [
            ['Name' => 'Steve'],
            ['Name' => 'Bob'],
            ['Name' => 'John']
        ];
        $this->assertEquals($expected, $list->toArray(), 'List should be unchanged');
    }

    /**
     * $list->exclude('Name', array('Steve','John'));
     */
    public function testSimpleExcludeWithArray()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Steve'],
            ['Name' => 'Bob'],
            ['Name' => 'John']
            ]
        );
        $list = $list->exclude('Name', ['Steve','John']);
        $expected = [['Name' => 'Bob']];
        $this->assertEquals(1, $list->count());
        $this->assertEquals($expected, $list->toArray(), 'List should only contain Bob');
    }

    /**
     * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude all Bob that has Age 21
     */
    public function testExcludeWithTwoArrays()
    {
        $list = new ArrayList(
            [
            ['Name' => 'Bob' , 'Age' => 21],
            ['Name' => 'Bob' , 'Age' => 32],
            ['Name' => 'John', 'Age' => 21]
            ]
        );

        $list = $list->exclude(['Name' => 'Bob', 'Age' => 21]);

        $expected = [
            ['Name' => 'Bob', 'Age' => 32],
            ['Name' => 'John', 'Age' => 21]
        ];

        $this->assertEquals(2, $list->count());
        $this->assertEquals($expected, $list->toArray(), 'List should only contain John and Bob');
    }

    /**
     * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(10, 16)));
     */
    public function testMultipleExclude()
    {
        $list = new ArrayList(
            [
            ['Name' => 'bob', 'Age' => 10],
            ['Name' => 'phil', 'Age' => 11],
            ['Name' => 'bob', 'Age' => 12],
            ['Name' => 'phil', 'Age' => 12],
            ['Name' => 'bob', 'Age' => 14],
            ['Name' => 'phil', 'Age' => 14],
            ['Name' => 'bob', 'Age' => 16],
            ['Name' => 'phil', 'Age' => 16]
            ]
        );

        $list = $list->exclude(['Name'=>['bob','phil'],'Age'=>[10, 16]]);
        $expected = [
            ['Name' => 'phil', 'Age' => 11],
            ['Name' => 'bob', 'Age' => 12],
            ['Name' => 'phil', 'Age' => 12],
            ['Name' => 'bob', 'Age' => 14],
            ['Name' => 'phil', 'Age' => 14],
        ];
        $this->assertEquals($expected, $list->toArray());
    }

    /**
     * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(10, 16), 'Bananas'=>true));
     */
    public function testMultipleExcludeNoMatch()
    {
        $list = new ArrayList(
            [
            ['Name' => 'bob', 'Age' => 10],
            ['Name' => 'phil', 'Age' => 11],
            ['Name' => 'bob', 'Age' => 12],
            ['Name' => 'phil', 'Age' => 12],
            ['Name' => 'bob', 'Age' => 14],
            ['Name' => 'phil', 'Age' => 14],
            ['Name' => 'bob', 'Age' => 16],
            ['Name' => 'phil', 'Age' => 16]
            ]
        );

        $list = $list->exclude(['Name'=>['bob','phil'],'Age'=>[10, 16],'Bananas'=>true]);
        $expected = [
            ['Name' => 'bob', 'Age' => 10],
            ['Name' => 'phil', 'Age' => 11],
            ['Name' => 'bob', 'Age' => 12],
            ['Name' => 'phil', 'Age' => 12],
            ['Name' => 'bob', 'Age' => 14],
            ['Name' => 'phil', 'Age' => 14],
            ['Name' => 'bob', 'Age' => 16],
            ['Name' => 'phil', 'Age' => 16]
        ];
        $this->assertEquals($expected, $list->toArray());
    }

    /**
     * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(10, 16), 'HasBananas'=>true));
     */
    public function testMultipleExcludeThreeArguments()
    {
        $list = new ArrayList(
            [
            ['Name' => 'bob', 'Age' => 10, 'HasBananas'=>false],
            ['Name' => 'phil','Age' => 11, 'HasBananas'=>true],
            ['Name' => 'bob', 'Age' => 12, 'HasBananas'=>true],
            ['Name' => 'phil','Age' => 12, 'HasBananas'=>true],
            ['Name' => 'bob', 'Age' => 14, 'HasBananas'=>false],
            ['Name' => 'ann', 'Age' => 14, 'HasBananas'=>true],
            ['Name' => 'phil','Age' => 14, 'HasBananas'=>false],
            ['Name' => 'bob', 'Age' => 16, 'HasBananas'=>false],
            ['Name' => 'phil','Age' => 16, 'HasBananas'=>true],
            ['Name' => 'clair','Age' => 16, 'HasBananas'=>true]
            ]
        );

        $list = $list->exclude(['Name'=>['bob','phil'],'Age'=>[10, 16],'HasBananas'=>true]);
        $expected = [
            ['Name' => 'bob', 'Age' => 10, 'HasBananas'=>false],
            ['Name' => 'phil','Age' => 11, 'HasBananas'=>true],
            ['Name' => 'bob', 'Age' => 12, 'HasBananas'=>true],
            ['Name' => 'phil','Age' => 12, 'HasBananas'=>true],
            ['Name' => 'bob', 'Age' => 14, 'HasBananas'=>false],
            ['Name' => 'ann', 'Age' => 14, 'HasBananas'=>true],
            ['Name' => 'phil','Age' => 14, 'HasBananas'=>false],
            ['Name' => 'bob', 'Age' => 16, 'HasBananas'=>false],
            ['Name' => 'clair','Age' => 16, 'HasBananas'=>true]
        ];
        $this->assertEquals($expected, $list->toArray());
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
