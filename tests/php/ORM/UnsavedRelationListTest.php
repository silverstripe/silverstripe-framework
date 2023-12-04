<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\RelationList;

class UnsavedRelationListTest extends SapphireTest
{
    protected static $fixture_file = 'UnsavedRelationListTest.yml';

    protected static $extra_dataobjects = [
        UnsavedRelationListTest\TestObject::class
    ];

    public function testReturnedList()
    {
        $object = new UnsavedRelationListTest\TestObject();
        $children = $object->Children();
        $siblings = $object->Siblings();
        $this->assertEquals(
            $children,
            $object->Children(),
            'Returned UnsavedRelationList should be the same.'
        );
        $this->assertEquals(
            $siblings,
            $object->Siblings(),
            'Returned UnsavedRelationList should be the same.'
        );

        $object->write();
        $this->assertInstanceOf(RelationList::class, $object->Children());
        $this->assertNotEquals(
            $children,
            $object->Children(),
            'Return should be a RelationList after first write'
        );
        $this->assertInstanceOf(RelationList::class, $object->Siblings());
        $this->assertNotEquals(
            $siblings,
            $object->Siblings(),
            'Return should be a RelationList after first write'
        );
    }

    public function testHasManyExisting()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $children->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectA'));
        $children->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectB'));
        $children->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectC'));

        $children = $object->Children();

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $children
        );

        $object->write();

        $this->assertNotEquals($children, $object->Children());

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $object->Children()
        );
    }

    public function testManyManyExisting()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $Siblings = $object->Siblings();
        $Siblings->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectA'));
        $Siblings->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectB'));
        $Siblings->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectC'));

        $siblings = $object->Siblings();

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $siblings
        );

        $object->write();

        $this->assertNotEquals($siblings, $object->Siblings());

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $object->Siblings()
        );
    }

    public function testHasManyNew()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'A']));
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'B']));
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'C']));

        $children = $object->Children();

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $children
        );

        $object->write();

        $this->assertNotEquals($children, $object->Children());

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $object->Children()
        );
    }

    public function testHasManyPolymorphic()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->RelatedObjects();
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'A']));
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'B']));
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'C']));

        $children = $object->RelatedObjects();

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $children
        );

        $object->write();

        $this->assertNotEquals($children, $object->RelatedObjects());

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $object->RelatedObjects()
        );
    }

    public function testManyManyNew()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $Siblings = $object->Siblings();
        $Siblings->add(new UnsavedRelationListTest\TestObject(['Name' => 'A']));
        $Siblings->add(new UnsavedRelationListTest\TestObject(['Name' => 'B']));
        $Siblings->add(new UnsavedRelationListTest\TestObject(['Name' => 'C']));

        $siblings = $object->Siblings();

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $siblings
        );

        $object->write();

        $this->assertNotEquals($siblings, $object->Siblings());

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $object->Siblings()
        );
    }

    public function testManyManyExtraFields()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $Siblings = $object->Siblings();
        $Siblings->add(new UnsavedRelationListTest\TestObject(['Name' => 'A']), ['Number' => 1]);
        $Siblings->add(new UnsavedRelationListTest\TestObject(['Name' => 'B']), ['Number' => 2]);
        $Siblings->add(new UnsavedRelationListTest\TestObject(['Name' => 'C']), ['Number' => 3]);

        $siblings = $object->Siblings();

        $this->assertListEquals(
            [
            ['Name' => 'A', 'Number' => 1],
            ['Name' => 'B', 'Number' => 2],
            ['Name' => 'C', 'Number' => 3]
            ],
            $siblings
        );

        $object->write();

        $this->assertNotEquals($siblings, $object->Siblings());

        $this->assertListEquals(
            [
            ['Name' => 'A', 'Number' => 1],
            ['Name' => 'B', 'Number' => 2],
            ['Name' => 'C', 'Number' => 3]
            ],
            $object->Siblings()
        );
    }

    public function testGetIDList()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $this->assertEquals($children->getIDList(), []);
        $children->add($child1 = new UnsavedRelationListTest\TestObject(['Name' => 'A']));
        $children->add($child2 = new UnsavedRelationListTest\TestObject(['Name' => 'B']));
        $children->add($child3 = new UnsavedRelationListTest\TestObject(['Name' => 'C']));
        $children->add($child1);

        $this->assertEquals($children->getIDList(), []);

        $child1->write();
        $this->assertEquals(
            $children->getIDList(),
            [
            $child1->ID => $child1->ID
            ]
        );

        $child2->write();
        $child3->write();
        $this->assertEquals(
            $children->getIDList(),
            [
            $child1->ID => $child1->ID,
            $child2->ID => $child2->ID,
            $child3->ID => $child3->ID
            ]
        );
    }

    public function testColumn()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'A']));
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'B']));
        $children->add(new UnsavedRelationListTest\TestObject(['Name' => 'C']));

        $children = $object->Children();

        $this->assertListEquals(
            [
            ['Name' => 'A'],
            ['Name' => 'B'],
            ['Name' => 'C']
            ],
            $children
        );

        $this->assertEquals(
            $children->column('Name'),
            [
            'A',
            'B',
            'C'
            ]
        );
    }

    public function testFirstAndLast()
    {
        $object = new UnsavedRelationListTest\TestObject();
        $children = $object->Children();

        $this->assertNull($children->first(), 'Empty UnsavedRelationList should return null for first item.');
        $this->assertNull($children->last(), 'Empty UnsavedRelationList should return null for last item.');

        $children->add($firstChild = $this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectA'));
        $children->add($this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectB'));
        $children->add($lastChild = $this->objFromFixture(UnsavedRelationListTest\TestObject::class, 'ObjectC'));

        $this->assertEquals($firstChild, $children->first(), 'Incorrect first item in UnsavedRelationList.');
        $this->assertEquals($lastChild, $children->last(), 'Incorrect last item in UnsavedRelationList.');
    }
}
