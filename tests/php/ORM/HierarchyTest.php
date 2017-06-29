<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\ValidationException;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Dev\SapphireTest;

class HierarchyTest extends SapphireTest
{
    protected static $fixture_file = 'HierarchyTest.yml';

    protected static $extra_dataobjects = array(
        HierarchyTest\TestObject::class,
        HierarchyTest\HideTestObject::class,
        HierarchyTest\HideTestSubObject::class,
    );

    public static function getExtraDataObjects()
    {
        // Prevent setup breaking if versioned module absent
        if (class_exists(Versioned::class)) {
            return parent::getExtraDataObjects();
        }
        return [];
    }

    public function setUp()
    {
        parent::setUp();

        // Note: Soft support for versioned module optionality
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('HierarchyTest requires the Versioned extension');
        }
    }

    /**
     * Test the Hierarchy prevents infinite loops.
     */
    public function testPreventLoop()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(sprintf(
            'Infinite loop found within the "%s" hierarchy',
            HierarchyTest\TestObject::class
        ));

        /** @var HierarchyTest\TestObject $obj2 */
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');

        $obj2->ParentID = $obj2aa->ID;
        $obj2->write();
    }

    /**
     * Test Hierarchy::AllHistoricalChildren().
     */
    public function testAllHistoricalChildren()
    {
        // Delete some objs
        $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b')->delete();
        $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3a')->delete();
        $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3')->delete();

        // Check that obj1-3 appear at the top level of the AllHistoricalChildren tree
        $this->assertEquals(
            array("Obj 1", "Obj 2", "Obj 3"),
            HierarchyTest\TestObject::singleton()->AllHistoricalChildren()->column('Title')
        );

        // Check numHistoricalChildren
        $this->assertEquals(3, HierarchyTest\TestObject::singleton()->numHistoricalChildren());

        // Check that both obj 2 children are returned
        /** @var HierarchyTest\TestObject $obj2 */
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $this->assertEquals(
            array("Obj 2a", "Obj 2b"),
            $obj2->AllHistoricalChildren()->column('Title')
        );

        // Check numHistoricalChildren
        $this->assertEquals(2, $obj2->numHistoricalChildren());


        // Obj 3 has been deleted; let's bring it back from the grave
        /** @var HierarchyTest\TestObject $obj3 */
        $obj3 = Versioned::get_including_deleted(
            HierarchyTest\TestObject::class,
            "\"Title\" = 'Obj 3'"
        )->First();

        // Check that all obj 3 children are returned
        $this->assertEquals(
            array("Obj 3a", "Obj 3b", "Obj 3c", "Obj 3d"),
            $obj3->AllHistoricalChildren()->column('Title')
        );

        // Check numHistoricalChildren
        $this->assertEquals(4, $obj3->numHistoricalChildren());
    }

    public function testNumChildren()
    {
        /** @var HierarchyTest\TestObject $obj1 */
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        /** @var HierarchyTest\TestObject $obj2 */
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        /** @var HierarchyTest\TestObject $obj3 */
        $obj3 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3');
        /** @var HierarchyTest\TestObject $obj2a */
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        /** @var HierarchyTest\TestObject $obj2b */
        $obj2b = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b');
        /** @var HierarchyTest\TestObject $obj3a */
        $obj3a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3a');
        /** @var HierarchyTest\TestObject $obj3b */
        $obj3b = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3d');

        $this->assertEquals(0, $obj1->numChildren());
        $this->assertEquals(2, $obj2->numChildren());
        $this->assertEquals(4, $obj3->numChildren());
        $this->assertEquals(2, $obj2a->numChildren());
        $this->assertEquals(0, $obj2b->numChildren());
        $this->assertEquals(2, $obj3a->numChildren());
        $this->assertEquals(0, $obj3b->numChildren());
        $obj1Child1 = new HierarchyTest\TestObject();
        $obj1Child1->ParentID = $obj1->ID;
        $obj1Child1->write();
        $this->assertEquals(
            $obj1->numChildren(false),
            1,
            'numChildren() caching can be disabled through method parameter'
        );
        $obj1Child2 = new HierarchyTest\TestObject();
        $obj1Child2->ParentID = $obj1->ID;
        $obj1Child2->write();
        $obj1->flushCache();
        $this->assertEquals(
            $obj1->numChildren(),
            2,
            'numChildren() caching can be disabled by flushCache()'
        );
    }

    public function testLoadDescendantIDListIntoArray()
    {
        /** @var HierarchyTest\TestObject $obj2 */
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        /** @var HierarchyTest\TestObject $obj2a */
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2b = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');
        $obj2ab = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2ab');

        $obj2IdList = $obj2->getDescendantIDList();
        $obj2aIdList = $obj2a->getDescendantIDList();

        $this->assertContains($obj2a->ID, $obj2IdList);
        $this->assertContains($obj2b->ID, $obj2IdList);
        $this->assertContains($obj2aa->ID, $obj2IdList);
        $this->assertContains($obj2ab->ID, $obj2IdList);
        $this->assertEquals(4, count($obj2IdList));

        $this->assertContains($obj2aa->ID, $obj2aIdList);
        $this->assertContains($obj2ab->ID, $obj2aIdList);
        $this->assertEquals(2, count($obj2aIdList));
    }

    /**
     * The "only deleted from stage" argument to liveChildren() should exclude
     * any page that has been moved to another location on the stage site
     */
    public function testLiveChildrenOnlyDeletedFromStage()
    {
        /** @var HierarchyTest\TestObject $obj1 */
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        /** @var HierarchyTest\TestObject $obj2 */
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        /** @var HierarchyTest\TestObject $obj2a */
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        /** @var HierarchyTest\TestObject $obj2b */
        $obj2b = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b');

        // Get a published set of objects for our fixture
        $obj1->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $obj2->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $obj2a->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $obj2b->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        // Then delete 2a from stage and move 2b to a sub-node of 1.
        $obj2a->delete();
        $obj2b->ParentID = $obj1->ID;
        $obj2b->write();

        // Get live children, excluding pages that have been moved on the stage site
        $children = $obj2->liveChildren(true, true)->column("Title");

        // 2a has been deleted from stage and should be shown
        $this->assertContains("Obj 2a", $children);

        // 2b has merely been moved to a different parent and so shouldn't be shown
        $this->assertNotContains("Obj 2b", $children);
    }

    public function testBreadcrumbs()
    {
        /** @var HierarchyTest\TestObject $obj1 */
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        /** @var HierarchyTest\TestObject $obj2a */
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        /** @var HierarchyTest\TestObject $obj2aa */
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');

        $this->assertEquals('Obj 1', $obj1->getBreadcrumbs());
        $this->assertEquals('Obj 2 &raquo; Obj 2a', $obj2a->getBreadcrumbs());
        $this->assertEquals('Obj 2 &raquo; Obj 2a &raquo; Obj 2aa', $obj2aa->getBreadcrumbs());
    }

    public function testNoHideFromHeirarchy()
    {
        /** @var HierarchyTest\HideTestObject $obj4 */
        $obj4 = $this->objFromFixture(HierarchyTest\HideTestObject::class, 'obj4');
        $obj4->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        foreach ($obj4->stageChildren() as $child) {
            $child->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        }
        $this->assertEquals($obj4->stageChildren()->Count(), 2);
        $this->assertEquals($obj4->liveChildren()->Count(), 2);
    }

    public function testHideFromHeirarchy()
    {
        HierarchyTest\HideTestObject::config()->update(
            'hide_from_hierarchy',
            [ HierarchyTest\HideTestSubObject::class ]
        );
        /** @var HierarchyTest\HideTestObject $obj4 */
        $obj4 = $this->objFromFixture(HierarchyTest\HideTestObject::class, 'obj4');
        $obj4->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        // load without using stage children otherwise it'll bbe filtered before it's publish
        // we need to publish all of them, and expect liveChildren to return some.
        $children = HierarchyTest\HideTestObject::get()
            ->filter('ParentID', (int)$obj4->ID)
            ->exclude('ID', (int)$obj4->ID);

        /** @var HierarchyTest\HideTestObject $child */
        foreach ($children as $child) {
            $child->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        }
        $this->assertEquals($obj4->stageChildren()->Count(), 1);
        $this->assertEquals($obj4->liveChildren()->Count(), 1);
    }
}
