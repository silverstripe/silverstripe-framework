<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;

/**
 * Test cascade delete objects
 */
class CascadeDeleteTest extends SapphireTest
{
    protected static $fixture_file = 'CascadeDeleteTest.yml';

    protected static $extra_dataobjects = [
        CascadeDeleteTest\ParentObject::class,
        CascadeDeleteTest\ChildObject::class,
        CascadeDeleteTest\GrandChildObject::class,
        CascadeDeleteTest\RelatedObject::class,
    ];

    public function testFindCascadeDeletes()
    {
        /** @var CascadeDeleteTest\ChildObject $child1 */
        $child1 = $this->objFromFixture(CascadeDeleteTest\ChildObject::class, 'child1');
        $this->assertListEquals(
            [
                [ 'Title' => 'Grandchild 1'],
                [ 'Title' => 'Grandchild 2'],
            ],
            $child1->findCascadeDeletes(true)
        );
        $this->assertListEquals(
            [
                [ 'Title' => 'Grandchild 1'],
                [ 'Title' => 'Grandchild 2'],
            ],
            $child1->findCascadeDeletes(false)
        );

        /** @var CascadeDeleteTest\ParentObject $parent1 */
        $parent1 = $this->objFromFixture(CascadeDeleteTest\ParentObject::class, 'parent1');
        $this->assertListEquals(
            [
                [ 'Title' => 'Child 1'],
                [ 'Title' => 'Grandchild 1'],
                [ 'Title' => 'Grandchild 2'],
            ],
            $parent1->findCascadeDeletes(true)
        );
        $this->assertListEquals(
            [
                [ 'Title' => 'Child 1'],
            ],
            $parent1->findCascadeDeletes(false)
        );
    }

    public function testRecursiveDelete()
    {
        /** @var CascadeDeleteTest\ChildObject $child1 */
        $child1 = $this->objFromFixture(CascadeDeleteTest\ChildObject::class, 'child1');
        $child1->delete();

        // Parent, related, and non-relational objects undeleted
        $this->assertNotEmpty($this->objFromFixture(CascadeDeleteTest\ParentObject::class, 'parent1'));

        // Related objects never deleted
        $this->assertListEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Related 2'],
                ['Title' => 'Related 3'],
            ],
            CascadeDeleteTest\RelatedObject::get()
        );

        // Ensure only remaining grandchild are those outside the relation
        $this->assertListEquals(
            [
                ['Title' => 'Grandchild 3'],
            ],
            CascadeDeleteTest\GrandChildObject::get()
        );
    }

    public function testDeepRecursiveDelete()
    {
        /** @var CascadeDeleteTest\ParentObject $parent1 */
        $parent1 = $this->objFromFixture(CascadeDeleteTest\ParentObject::class, 'parent1');
        $parent1->delete();

        // Ensure affected cascading tables have expected content
        $this->assertListEquals(
            [
                ['Title' => 'Child 2'],
            ],
            CascadeDeleteTest\ChildObject::get()
        );
        $this->assertListEquals(
            [
                ['Title' => 'Grandchild 3'],
            ],
            CascadeDeleteTest\GrandChildObject::get()
        );

        // Related objects never deleted
        $this->assertListEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Related 2'],
                ['Title' => 'Related 3'],
            ],
            CascadeDeleteTest\RelatedObject::get()
        );

        // Ensure that other parents which share cascade deleted objects have the correct result
        /** @var CascadeDeleteTest\ChildObject $child2 */
        $child2 = $this->objFromFixture(CascadeDeleteTest\ChildObject::class, 'child2');
        $this->assertListEquals(
            [
                ['Title' => 'Grandchild 3'],
            ],
            $child2->Children()
        );
    }
}
