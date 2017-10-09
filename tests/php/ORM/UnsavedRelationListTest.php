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
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $children
        );

        $object->write();

        $this->assertNotEquals($children, $object->Children());

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
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
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $siblings
        );

        $object->write();

        $this->assertNotEquals($siblings, $object->Siblings());

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $object->Siblings()
        );
    }

    public function testHasManyNew()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'A')));
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'B')));
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'C')));

        $children = $object->Children();

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $children
        );

        $object->write();

        $this->assertNotEquals($children, $object->Children());

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $object->Children()
        );
    }

    public function testHasManyPolymorphic()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->RelatedObjects();
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'A')));
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'B')));
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'C')));

        $children = $object->RelatedObjects();

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $children
        );

        $object->write();

        $this->assertNotEquals($children, $object->RelatedObjects());

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $object->RelatedObjects()
        );
    }

    public function testManyManyNew()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $Siblings = $object->Siblings();
        $Siblings->add(new UnsavedRelationListTest\TestObject(array('Name' => 'A')));
        $Siblings->add(new UnsavedRelationListTest\TestObject(array('Name' => 'B')));
        $Siblings->add(new UnsavedRelationListTest\TestObject(array('Name' => 'C')));

        $siblings = $object->Siblings();

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $siblings
        );

        $object->write();

        $this->assertNotEquals($siblings, $object->Siblings());

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $object->Siblings()
        );
    }

    public function testManyManyExtraFields()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $Siblings = $object->Siblings();
        $Siblings->add(new UnsavedRelationListTest\TestObject(array('Name' => 'A')), array('Number' => 1));
        $Siblings->add(new UnsavedRelationListTest\TestObject(array('Name' => 'B')), array('Number' => 2));
        $Siblings->add(new UnsavedRelationListTest\TestObject(array('Name' => 'C')), array('Number' => 3));

        $siblings = $object->Siblings();

        $this->assertListEquals(
            array(
            array('Name' => 'A', 'Number' => 1),
            array('Name' => 'B', 'Number' => 2),
            array('Name' => 'C', 'Number' => 3)
            ),
            $siblings
        );

        $object->write();

        $this->assertNotEquals($siblings, $object->Siblings());

        $this->assertListEquals(
            array(
            array('Name' => 'A', 'Number' => 1),
            array('Name' => 'B', 'Number' => 2),
            array('Name' => 'C', 'Number' => 3)
            ),
            $object->Siblings()
        );
    }

    public function testGetIDList()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $this->assertEquals($children->getIDList(), array());
        $children->add($child1 = new UnsavedRelationListTest\TestObject(array('Name' => 'A')));
        $children->add($child2 = new UnsavedRelationListTest\TestObject(array('Name' => 'B')));
        $children->add($child3 = new UnsavedRelationListTest\TestObject(array('Name' => 'C')));
        $children->add($child1);

        $this->assertEquals($children->getIDList(), array());

        $child1->write();
        $this->assertEquals(
            $children->getIDList(),
            array(
            $child1->ID => $child1->ID
            )
        );

        $child2->write();
        $child3->write();
        $this->assertEquals(
            $children->getIDList(),
            array(
            $child1->ID => $child1->ID,
            $child2->ID => $child2->ID,
            $child3->ID => $child3->ID
            )
        );
    }

    public function testColumn()
    {
        $object = new UnsavedRelationListTest\TestObject();

        $children = $object->Children();
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'A')));
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'B')));
        $children->add(new UnsavedRelationListTest\TestObject(array('Name' => 'C')));

        $children = $object->Children();

        $this->assertListEquals(
            array(
            array('Name' => 'A'),
            array('Name' => 'B'),
            array('Name' => 'C')
            ),
            $children
        );

        $this->assertEquals(
            $children->column('Name'),
            array(
            'A',
            'B',
            'C'
            )
        );
    }
}
