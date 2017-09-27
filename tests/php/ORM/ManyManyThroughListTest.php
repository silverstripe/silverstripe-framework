<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;
use InvalidArgumentException;

class ManyManyThroughListTest extends SapphireTest
{
    protected static $fixture_file = 'ManyManyThroughListTest.yml';

    protected static $extra_dataobjects = [
        ManyManyThroughListTest\Item::class,
        ManyManyThroughListTest\JoinObject::class,
        ManyManyThroughListTest\TestObject::class
    ];

    protected function setUp()
    {
        parent::setUp();
        DataObject::reset();
    }

    protected function tearDown()
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

        // Test sorting on join table
        $items = $parent->Items()->sort('"ManyManyThroughListTest_JoinObject"."Sort"');
        $this->assertListEquals(
            [
                ['Title' => 'item 2'],
                ['Title' => 'item 1'],
            ],
            $items
        );

        $items = $parent->Items()->sort('"ManyManyThroughListTest_JoinObject"."Sort" ASC');
        $this->assertListEquals(
            [
                ['Title' => 'item 1'],
                ['Title' => 'item 2'],
            ],
            $items
        );
        $items = $parent->Items()->sort('"ManyManyThroughListTest_JoinObject"."Title" DESC');
        $this->assertListEquals(
            [
                ['Title' => 'item 2'],
                ['Title' => 'item 1'],
            ],
            $items
        );
    }

    public function testAdd()
    {
        /** @var ManyManyThroughListTest\TestObject $parent */
        $parent = $this->objFromFixture(ManyManyThroughListTest\TestObject::class, 'parent1');
        $newItem = new ManyManyThroughListTest\Item();
        $newItem->Title = 'my new item';
        $newItem->write();
        $parent->Items()->add($newItem, ['Title' => 'new join record']);

        // Check select
        $newItem = $parent->Items()->filter(['Title' => 'my new item'])->first();
        $this->assertNotNull($newItem);
        $this->assertEquals('my new item', $newItem->Title);
        $this->assertInstanceOf(
            ManyManyThroughListTest\JoinObject::class,
            $newItem->getJoin()
        );
        $this->assertInstanceOf(
            ManyManyThroughListTest\JoinObject::class,
            $newItem->ManyManyThroughListTest_JoinObject
        );
        $this->assertEquals('new join record', $newItem->ManyManyThroughListTest_JoinObject->Title);
    }

    public function testRemove()
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
        $item1 = $parent->Items()->filter(['Title' => 'item 1'])->first();
        $parent->Items()->remove($item1);
        $this->assertListEquals(
            [['Title' => 'item 2']],
            $parent->Items()
        );
    }

    /**
     * Test validation
     *
     * @expectedException \InvalidArgumentException
     */
    public function testValidateModelValidatesJoinType()
    {
        DataObject::reset();
        ManyManyThroughListTest\Item::config()->update(
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
}
