<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\ManyManyThroughListTest\Item;
use SilverStripe\ORM\Tests\ManyManyThroughListTest\PolyItem;
use SilverStripe\ORM\Tests\ManyManyThroughListTest\PolyJoinObject;
use SilverStripe\ORM\Tests\ManyManyThroughListTest\Locale;
use SilverStripe\ORM\Tests\ManyManyThroughListTest\FallbackLocale;
use SilverStripe\ORM\Tests\ManyManyThroughListTest\TestObject;
use SilverStripe\ORM\DataList;

class ManyManyThroughListTest extends SapphireTest
{
    protected static $fixture_file = 'ManyManyThroughListTest.yml';

    protected static $extra_dataobjects = [
        ManyManyThroughListTest\Item::class,
        ManyManyThroughListTest\JoinObject::class,
        ManyManyThroughListTest\TestObject::class,
        ManyManyThroughListTest\TestObjectSubclass::class,
        ManyManyThroughListTest\PolyItem::class,
        ManyManyThroughListTest\PolyJoinObject::class,
        ManyManyThroughListTest\PolyObjectA::class,
        ManyManyThroughListTest\PolyObjectB::class,
        ManyManyThroughListTest\PseudoPolyJoinObject::class,
        ManyManyThroughListTest\Locale::class,
        ManyManyThroughListTest\FallbackLocale::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        DataObject::reset();
    }

    protected function tearDown(): void
    {
        DataObject::reset();
        parent::tearDown();
    }

    public function testSelectJoin()
    {
        /** @var ManyManyThroughListTest\TestObject $parent */
        $parent = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $this->assertListEquals(
            [
                ['Title' => 'item 1'],
                ['Title' => 'item 2']
            ],
            $parent->Items()
        );
        // Check filters on list work
        $item1 = $parent->Items()->filter('Title', 'item 1')->first();
        $this->assertNotNull($item1);
        $this->assertNotNull($item1->getJoin());
        $this->assertEquals('join 1', $item1->getJoin()->Title);
        $this->assertInstanceOf(
            ManyManyThroughListTest\JoinObject::class,
            $item1->ManyManyThroughListTest_JoinObject
        );
        $this->assertEquals('join 1', $item1->ManyManyThroughListTest_JoinObject->Title);

        // Check filters on list work
        $item2 = $parent->Items()->filter('Title', 'item 2')->first();
        $this->assertNotNull($item2);
        $this->assertNotNull($item2->getJoin());
        $this->assertEquals('join 2', $item2->getJoin()->Title);
        $this->assertEquals('join 2', $item2->ManyManyThroughListTest_JoinObject->Title);

        // To filter on join table need to use some raw sql
        $item2 = $parent->Items()->where(['"ManyManyThroughListTest_JoinObject"."Title"' => 'join 2'])->first();
        $this->assertNotNull($item2);
        $this->assertEquals('item 2', $item2->Title);
        $this->assertNotNull($item2->getJoin());
        $this->assertEquals('join 2', $item2->getJoin()->Title);
        $this->assertEquals('join 2', $item2->ManyManyThroughListTest_JoinObject->Title);

        // Check that the join record is set for new records added
        $item3 = new Item;
        $this->assertNull($item3->getJoin());
        $parent->Items()->add($item3);
        $expectedJoinObject = ManyManyThroughListTest\JoinObject::get()->filter(['ParentID' => $parent->ID, 'ChildID' => $item3->ID ])->first();
        $this->assertEquals($expectedJoinObject->ID, $item3->getJoin()->ID);
        $this->assertEquals(get_class($expectedJoinObject), get_class($item3->getJoin()));
    }

    /**
     * @param string $sort
     * @param array $expected
     * @dataProvider sortingProvider
     */
    public function testSorting($sort, $expected)
    {
        /** @var ManyManyThroughListTest\TestObject $parent */
        $parent = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');

        $items = $parent->Items();
        if ($sort) {
            $items = $items->orderBy($sort);
        }
        $this->assertSame($expected, $items->column('Title'));
    }

    /**
     * @return array[]
     */
    public function sortingProvider()
    {
        return [
            'nothing passed (default)' => [
                null,
                ['item 2', 'item 1'],
            ],
            'table with default column' => [
                '"ManyManyThroughListTest_JoinObject"."Sort"',
                ['item 2', 'item 1'],
            ],
            'table with default column ascending' => [
                '"ManyManyThroughListTest_JoinObject"."Sort" ASC',
                ['item 2', 'item 1'],
            ],
            'table with default column descending' => [
                '"ManyManyThroughListTest_JoinObject"."Sort" DESC',
                ['item 1', 'item 2'],
            ],
            'table with column descending' => [
                '"ManyManyThroughListTest_JoinObject"."Title" DESC',
                ['item 2', 'item 1'],
            ],
            'table with column ascending' => [
                '"ManyManyThroughListTest_JoinObject"."Title" ASC',
                ['item 1', 'item 2'],
            ],
            'default column' => [
                '"Sort"',
                ['item 2', 'item 1'],
            ],
            'default column ascending' => [
                '"Sort" ASC',
                ['item 2', 'item 1'],
            ],
            'default column descending' => [
                '"Sort" DESC',
                ['item 1', 'item 2'],
            ],
            'column descending' => [
                '"Title" DESC',
                ['item 2', 'item 1'],
            ],
            'column ascending' => [
                '"Title" ASC',
                ['item 1', 'item 2'],
            ],
        ];
    }

    public function provideAdd(): array
    {
        return [
            [
                'parentClass' => ManyManyThroughListTest\TestObject::class,
                'joinClass' => ManyManyThroughListTest\JoinObject::class,
                'joinProperty' => 'ManyManyThroughListTest_JoinObject',
                'relation' => 'Items',
            ],
            [
                'parentClass' => ManyManyThroughListTest\TestObjectSubclass::class,
                'joinClass' => ManyManyThroughListTest\PseudoPolyJoinObject::class,
                'joinProperty' => 'ManyManyThroughListTest_PseudoPolyJoinObject',
                'relation' => 'MoreItems',
            ],
        ];
    }

    /**
     * @dataProvider provideAdd
     */
    public function testAdd(string $parentClass, string $joinClass, string $joinProperty, string $relation)
    {
        $parent = $this->objFromFixture($parentClass, 'parent1');
        $newItem = new ManyManyThroughListTest\Item();
        $newItem->Title = 'my new item';
        $newItem->write();
        $parent->$relation()->add($newItem, ['Title' => 'new join record']);

        // Check select
        $newItem = $parent->$relation()->filter(['Title' => 'my new item'])->first();
        $this->assertNotNull($newItem);
        $this->assertEquals('my new item', $newItem->Title);
        $this->assertInstanceOf(
            $joinClass,
            $newItem->getJoin()
        );
        $this->assertInstanceOf(
            $joinClass,
            $newItem->$joinProperty
        );
        $this->assertEquals('new join record', $newItem->$joinProperty->Title);
    }

    public function provideRemove(): array
    {
        return [
            [
                'parentClass' => ManyManyThroughListTest\TestObject::class,
                'relation' => 'Items',
            ],
            [
                'parentClass' => ManyManyThroughListTest\TestObjectSubclass::class,
                'relation' => 'MoreItems',
            ],
        ];
    }

    /**
     * @dataProvider provideRemove
     */
    public function testRemove(string $parentClass, string $relation)
    {
        $parent = $this->objFromFixture($parentClass, 'parent1');
        $this->assertListEquals(
            [
                ['Title' => 'item 1'],
                ['Title' => 'item 2']
            ],
            $parent->$relation()
        );
        $item1 = $parent->$relation()->filter(['Title' => 'item 1'])->first();
        $parent->$relation()->remove($item1);
        $this->assertListEquals(
            [['Title' => 'item 2']],
            $parent->$relation()
        );
    }

    public function testRemoveAll()
    {
        $first = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $first->Items()->add($this->objFromFixture(ManyManyThroughListTest\Item::class, 'child0'));
        $second = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent2');

        $firstItems = $first->Items();
        $secondItems = $second->Items();
        $initialJoins = ManyManyThroughListTest\JoinObject::get()->count();
        $initialItems = ManyManyThroughListTest\Item::get()->count();
        $initialRelations = $firstItems->count();
        $initialSecondListRelations = $secondItems->count();

        $firstItems->removeAll();

        // Validate all items were removed from the first list, but none were removed from the second list
        $this->assertEquals(0, count($firstItems));
        $this->assertEquals($initialSecondListRelations, count($secondItems));

        // Validate that the JoinObjects were actually removed from the database
        $this->assertEquals($initialJoins - $initialRelations, ManyManyThroughListTest\JoinObject::get()->count());

        // Confirm Item objects were not removed from the database
        $this->assertEquals($initialItems, ManyManyThroughListTest\Item::get()->count());
    }

    public function testRemoveAllIgnoresLimit()
    {
        $parent = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $parent->Items()->add($this->objFromFixture(ManyManyThroughListTest\Item::class, 'child0'));
        $initialJoins = ManyManyThroughListTest\JoinObject::get()->count();
        // Validate there are enough items in the relation for this test
        $this->assertTrue($initialJoins > 1);

        $items = $parent->Items()->Limit(1);
        $items->removeAll();

        // Validate all items were removed from the list - not only one
        $this->assertEquals(0, count($items));
    }

    public function testFilteredRemoveAll()
    {
        $parent = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $parent->Items()->add($this->objFromFixture(ManyManyThroughListTest\Item::class, 'child0'));
        $items = $parent->Items();
        $initialJoins = ManyManyThroughListTest\JoinObject::get()->count();
        $initialRelations = $items->count();

        $items->filter('Title:not', 'not filtered')->removeAll();

        // Validate only the filtered items were removed
        $this->assertEquals(1, $items->count());

        // Validate the list only contains the correct remaining item
        $this->assertEquals(['not filtered'], $items->column('Title'));
    }

    /**
     * Test validation
     */
    public function testValidateModelValidatesJoinType()
    {
        $this->expectException(\InvalidArgumentException::class);
        DataObject::reset();
        ManyManyThroughListTest\Item::config()->merge(
            'db',
            [
            ManyManyThroughListTest\JoinObject::class => 'Text'
            ]
        );

        DataObject::getSchema()->manyManyComponent(ManyManyThroughListTest\TestObject::class, 'Items');
    }

    public function testRelationParsing()
    {
        $schema = DataObject::getSchema();

        // Parent components
        $this->assertEquals(
            [
                'relationClass' => ManyManyThroughList::class,
                'parentClass' => ManyManyThroughListTest\TestObject::class,
                'childClass' => ManyManyThroughListTest\Item::class,
                'parentField' => 'ParentID',
                'childField' => 'ChildID',
                'join' => ManyManyThroughListTest\JoinObject::class
            ],
            $schema->manyManyComponent(ManyManyThroughListTest\TestObject::class, 'Items')
        );

        // Belongs_many_many is the same, but with parent/child substituted
        $this->assertEquals(
            [
                'relationClass' => ManyManyThroughList::class,
                'parentClass' => ManyManyThroughListTest\Item::class,
                'childClass' => ManyManyThroughListTest\TestObject::class,
                'parentField' => 'ChildID',
                'childField' => 'ParentID',
                'join' => ManyManyThroughListTest\JoinObject::class
            ],
            $schema->manyManyComponent(ManyManyThroughListTest\Item::class, 'Objects')
        );
    }

    /**
     * Note: polymorphic many_many support is currently experimental
     */
    public function testPolymorphicManyMany()
    {
        /** @var ManyManyThroughListTest\PolyObjectA $objA1 */
        $objA1 = $this->objFromFixture(ManyManyThroughListTest\PolyObjectA::class, 'obja1');
        /** @var ManyManyThroughListTest\PolyObjectB $objB1 */
        $objB1 = $this->objFromFixture(ManyManyThroughListTest\PolyObjectB::class, 'objb1');
        /** @var ManyManyThroughListTest\PolyObjectB $objB2 */
        $objB2 = $this->objFromFixture(ManyManyThroughListTest\PolyObjectB::class, 'objb2');

        // Test various parent class queries
        $this->assertListEquals([
            ['Title' => 'item 1'],
            ['Title' => 'item 2'],
        ], $objA1->Items());
        $this->assertListEquals([
            ['Title' => 'item 2'],
        ], $objB1->Items());
        $this->assertListEquals([
            ['Title' => 'item 2'],
        ], $objB2->Items());

        // Test adding items
        $newItem = new PolyItem();
        $newItem->Title = 'New Item';
        $objA1->Items()->add($newItem);
        $objB2->Items()->add($newItem);
        $this->assertListEquals([
            ['Title' => 'item 1'],
            ['Title' => 'item 2'],
            ['Title' => 'New Item'],
        ], $objA1->Items());
        $this->assertListEquals([
            ['Title' => 'item 2'],
        ], $objB1->Items());
        $this->assertListEquals([
            ['Title' => 'item 2'],
            ['Title' => 'New Item'],
        ], $objB2->Items());

        // Test removing items
        $item2 = $this->objFromFixture(ManyManyThroughListTest\PolyItem::class, 'child2');
        $objA1->Items()->remove($item2);
        $objB1->Items()->remove($item2);
        $this->assertListEquals([
            ['Title' => 'item 1'],
            ['Title' => 'New Item'],
        ], $objA1->Items());
        $this->assertListEquals([], $objB1->Items());
        $this->assertListEquals([
            ['Title' => 'item 2'],
            ['Title' => 'New Item'],
        ], $objB2->Items());

        // Test set-by-id-list
        $objB2->Items()->setByIDList([
            $newItem->ID,
            $this->idFromFixture(ManyManyThroughListTest\PolyItem::class, 'child1'),
        ]);
        $this->assertListEquals([
            ['Title' => 'item 1'],
            ['Title' => 'New Item'],
        ], $objA1->Items());
        $this->assertListEquals([], $objB1->Items());
        $this->assertListEquals([
            ['Title' => 'item 1'],
            ['Title' => 'New Item'],
        ], $objB2->Items());
    }

    public function testGetJoinTable()
    {
        $joinTable = DataObject::getSchema()->tableName(PolyJoinObject::class);
        /** @var ManyManyThroughListTest\PolyObjectA $objA1 */
        $objA1 = $this->objFromFixture(ManyManyThroughListTest\PolyObjectA::class, 'obja1');
        /** @var ManyManyThroughListTest\PolyObjectB $objB1 */
        $objB1 = $this->objFromFixture(ManyManyThroughListTest\PolyObjectB::class, 'objb1');
        /** @var ManyManyThroughListTest\PolyObjectB $objB2 */
        $objB2 = $this->objFromFixture(ManyManyThroughListTest\PolyObjectB::class, 'objb2');

        $this->assertEquals($joinTable, $objA1->Items()->getJoinTable());
        $this->assertEquals($joinTable, $objB1->Items()->getJoinTable());
        $this->assertEquals($joinTable, $objB2->Items()->getJoinTable());
    }

    /**
     * This tests that default sort works when the join table has a default sort set, and the main
     * dataobject has a default sort set.
     *
     * @return void
     */
    public function testDefaultSortOnJoinAndMain()
    {
        // We have spanish mexico with two fall back locales; argentina and international sorted in that order.
        $mexico = $this->objFromFixture(Locale::class, 'mexico');

        $fallbacks = $mexico->Fallbacks();
        $this->assertCount(2, $fallbacks);

        // Ensure the default sort is is correct
        list($first, $second) = $fallbacks;
        $this->assertSame('Argentina', $first->Title);
        $this->assertSame('International', $second->Title);

        // Ensure that we're respecting the default sort by reversing it
        Config::inst()->set(FallbackLocale::class, 'default_sort', '"ManyManyThroughTest_FallbackLocale"."Sort" DESC');

        $reverse = $mexico->Fallbacks();
        list($firstReverse, $secondReverse) = $reverse;
        $this->assertSame('International', $firstReverse->Title);
        $this->assertSame('Argentina', $secondReverse->Title);
    }

    public function testCallbackOnSetById()
    {
        $addedIds = [];
        $removedIds = [];

        $base = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $relation = $base->Items();
        $remove = $relation->First();
        $add = new Item();
        $add->write();

        $relation->addCallbacks()->add(function ($list, $item, $extraFields) use (&$removedIds) {
            $addedIds[] = $item;
        });

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->setByIDList(array_merge(
            $base->Items()->exclude('ID', $remove->ID)->column('ID'),
            [$add->ID]
        ));
        $this->assertEquals([$remove->ID], $removedIds);
    }

    public function testAddCallbackWithExtraFields()
    {
        $added = [];

        $base = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $relation = $base->Items();
        $add = new Item();
        $add->write();

        $relation->addCallbacks()->add(function ($list, $item, $extraFields) use (&$added) {
            $added[] = [$item, $extraFields];
        });

        $relation->add($add, ['Sort' => '99']);
        $this->assertEquals([[$add, ['Sort' => '99']]], $added);
    }

    public function testRemoveCallbackOnRemove()
    {
        $removedIds = [];

        $base = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $relation = $base->Items();
        $remove = $relation->First();

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->remove($remove);
        $this->assertEquals([$remove->ID], $removedIds);
    }

    public function testRemoveCallbackOnRemoveById()
    {
        $removedIds = [];

        $base = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $relation = $base->Items();
        $remove = $relation->First();

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->removeByID($remove->ID);
        $this->assertEquals([$remove->ID], $removedIds);
    }

    public function testRemoveCallbackOnRemoveAll()
    {
        $removedIds = [];

        $base = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $relation = $base->Items();
        $remove = $relation->column('ID');

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->removeAll();
        $this->assertEquals(sort($remove), sort($removedIds));
    }

    /**
     * @dataProvider provideForForeignIDPlaceholders
     */
    public function testForForeignIDPlaceholders(bool $config, bool $useInt, bool $expected): void
    {
        Config::modify()->set(DataList::class, 'use_placeholders_for_integer_ids', $config);
        $parent1 = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $parent2 = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent2');
        $items1 = $parent1->Items();
        $items2 = $parent2->Items();
        $ids = $useInt ? [$parent1->ID, $parent2->ID] : ['Lorem', 'Ipsum'];
        $newItemsList = $items1->forForeignID($ids);
        $sql = $newItemsList->dataQuery()->sql();
        preg_match('#ID" IN \(([^\)]+)\)\)#', $sql, $matches);
        $usesPlaceholders = $matches[1] === '?, ?';
        $this->assertSame($expected, $usesPlaceholders);
        $expectedIDs = $useInt
            ? array_values(array_merge($items1->column('ID'), $items2->column('ID')))
            : [];
        $this->assertEqualsCanonicalizing($expectedIDs, $newItemsList->column('ID'));
    }

    public function provideForForeignIDPlaceholders(): array
    {
        return [
            'config false' => [
                'config' => false,
                'useInt' => true,
                'expected' => false,
            ],
            'config false non-int' => [
                'config' => false,
                'useInt' => false,
                'expected' => true,
            ],
            'config true' => [
                'config' => true,
                'useInt' => true,
                'expected' => true,
            ],
            'config true non-int' => [
                'config' => true,
                'useInt' => false,
                'expected' => true,
            ],
        ];
    }

    public function testChangedFields()
    {
        /** @var ManyManyThroughListTest\TestObject $parent */
        $parent = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $item1 = $parent->Items()->first();

        // Nothing has changed yet
        $this->assertEmpty($item1->getChangedFields());
        $this->assertFalse($item1->isChanged('Title'));

        // Change a field, ensure change is flagged
        $item1->Title = 'a test title';
        $this->assertTrue($item1->isChanged('Title'));
    }
}
