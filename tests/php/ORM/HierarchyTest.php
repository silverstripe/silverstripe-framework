<?php

namespace SilverStripe\ORM\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class HierarchyTest extends SapphireTest
{
    protected static $fixture_file = 'HierarchyTest.yml';

    protected static $extra_dataobjects = [
        HierarchyTest\TestObject::class,
        HierarchyTest\HideTestObject::class,
        HierarchyTest\HideTestSubObject::class,
        HierarchyTest\HierarchyOnSubclassTestObject::class,
        HierarchyTest\HierarchyOnSubclassTestSubObject::class,
        HierarchyTest\NoEditTestObject::class,
        HierarchyTest\HierarchyModel::class,
        HierarchyTest\SortableHierarchyModel::class,
        HierarchyTest\TestAllowedChildrenA::class,
        HierarchyTest\TestAllowedChildrenB::class,
        HierarchyTest\TestAllowedChildrenC::class,
        HierarchyTest\TestAllowedChildrenCext::class,
        HierarchyTest\TestAllowedChildrenD::class,
        HierarchyTest\TestAllowedChildrenE::class,
        HierarchyTest\TestAllowedChildrenHidden::class,
    ];

    public static function getExtraDataObjects()
    {
        // Prevent setup breaking if versioned module absent
        if (class_exists(Versioned::class)) {
            return parent::getExtraDataObjects();
        }
        return [];
    }

    protected function setUp(): void
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
            ['Obj 1', 'Obj 2', 'Obj 3', 'Obj no-edit 1'],
            HierarchyTest\TestObject::singleton()->AllHistoricalChildren()->column('Title')
        );

        // Check numHistoricalChildren
        $this->assertEquals(4, HierarchyTest\TestObject::singleton()->numHistoricalChildren());

        // Check that both obj 2 children are returned
        /** @var HierarchyTest\TestObject $obj2 */
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $this->assertEquals(
            ["Obj 2a", "Obj 2b"],
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
            ["Obj 3a", "Obj 3b", "Obj 3c", "Obj 3d"],
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

    public function testNumChildrenHierarchyOnSubclass()
    {
        /** @var HierarchyTest\HierarchyOnSubclassTestObject $obj5 */
        $obj5 = $this->objFromFixture(HierarchyTest\HierarchyOnSubclassTestObject::class, 'obj5');

        $this->assertFalse(
            $obj5->hasMethod('numChildren'),
            'numChildren() cannot be called on object without Hierarchy extension'
        );

        /** @var HierarchyTest\HierarchyOnSubclassTestSubObject $obj5a */
        $obj5a = $this->objFromFixture(HierarchyTest\HierarchyOnSubclassTestSubObject::class, 'obj5a');
        /** @var HierarchyTest\HierarchyOnSubclassTestSubObject $obj5b */
        $obj5b = $this->objFromFixture(HierarchyTest\HierarchyOnSubclassTestSubObject::class, 'obj5b');

        $this->assertEquals(2, $obj5a->numChildren());
        $this->assertEquals(1, $obj5b->numChildren());

        $obj5bChild2 = new HierarchyTest\HierarchyOnSubclassTestSubObject();
        $obj5bChild2->ParentID = $obj5b->ID;
        $obj5bChild2->write();
        $this->assertEquals(
            $obj5b->numChildren(false),
            2,
            'numChildren() caching can be disabled through method parameter'
        );
        $obj5bChild3 = new HierarchyTest\HierarchyOnSubclassTestSubObject();
        $obj5bChild3->ParentID = $obj5b->ID;
        $obj5bChild3->write();
        $obj5b->flushCache();
        $this->assertEquals(
            $obj5b->numChildren(),
            3,
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
        $this->assertEquals(4, count($obj2IdList ?? []));

        $this->assertContains($obj2aa->ID, $obj2aIdList);
        $this->assertContains($obj2ab->ID, $obj2aIdList);
        $this->assertEquals(2, count($obj2aIdList ?? []));
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

    public function testNoHideFromHierarchy()
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

    public function testHideFromHierarchy()
    {
        HierarchyTest\HideTestObject::config()->merge(
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

    /**
     * Check canCreate permissions respect allowed_children config.
     *
     * Note we are intentionally note testing all possible allowed_children config here since allowedChildren()
     * will be called and there are dedicated tests for that method.
     */
    public function testCanCreate(): void
    {
        $singleton = singleton(HierarchyTest\TestObject::class);
        $reflectionHierarchy = new ReflectionClass(Hierarchy::class);
        $reflectionHierarchy->setStaticPropertyValue('cache_allowedChildren', []);

        // Test logged out users cannot create (i.e. we're not breaking default permissions)
        $this->logOut();
        $this->assertFalse($singleton->canCreate());

        // Login with admin permissions (default return true on DataObject)
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($singleton->canCreate());

        // Test creation underneath a parent which this user can edit
        $parent = $this->objFromFixture(HierarchyTest\HideTestObject::class, 'obj4');
        $this->assertTrue($singleton->canCreate(null, ['Parent' => $parent]));

        // Test creation underneath a parent which this user CANNOT edit
        $parent = $this->objFromFixture(HierarchyTest\NoEditTestObject::class, 'no-edit1');
        $this->assertFalse($singleton->canCreate(null, ['Parent' => $parent]));

        // Test creation underneath a parent which explicitly allows it
        HierarchyTest\HideTestSubObject::config()->set('allowed_children', [HierarchyTest\HideTestObject::class]);
        $singleton2 = HierarchyTest\HideTestObject::singleton();
        $reflectionHierarchy->setStaticPropertyValue('cache_allowedChildren', []);
        $parent = $this->objFromFixture(HierarchyTest\HideTestSubObject::class, 'obj4b');
        $this->assertTrue($singleton2->canCreate(null, ['Parent' => $parent]));

        // Test creation underneath a parent which implicitly does NOT allow it
        HierarchyTest\HideTestSubObject::config()->set('allowed_children', [HierarchyTest\HideTestSubObject::class]);
        $reflectionHierarchy->setStaticPropertyValue('cache_allowedChildren', []);
        $parent = $this->objFromFixture(HierarchyTest\HideTestSubObject::class, 'obj4b');
        $this->assertFalse($singleton2->canCreate(null, ['Parent' => $parent]));

        // Test we don't check for allowedChildren on parent context if it's not in the same hierarchy
        $parent = $this->objFromFixture(HierarchyTest\HideTestObject::class, 'obj4');
        HierarchyTest\HideTestObject::config()->set('allowed_children', [HierarchyTest\HideTestObject::class]);
        $this->assertTrue($singleton->canCreate(null, ['Parent' => $parent]));
    }

    public function testCanAddChildren()
    {
        $record = new HierarchyTest\TestObject();

        // Can't add children if unauthenticated (default canEdit permissions)
        $this->logOut();
        $this->assertFalse($record->canAddChildren());

        // Admin can add children by default
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($record->canAddChildren());

        // Can't add children to archived records
        $record->publishSingle();
        $record->doArchive();
        $this->assertFalse($record->canAddChildren());

        // Can't add children to models that don't allow children
        $record = new HierarchyTest\TestAllowedChildrenE();
        $this->assertFalse($record->canAddChildren());

        // Can't edit, so can't add children
        $record = new HierarchyTest\NoEditTestObject();
        $this->assertFalse($record->canAddChildren());
    }

    public static function provideAllowedChildren(): array
    {
        return [
            'implicitly allows entire unhidden hierarchy' => [
                'className' => HierarchyTest\HierarchyModel::class,
                'expected' => [
                    HierarchyTest\HierarchyModel::class,
                    HierarchyTest\TestAllowedChildrenA::class,
                    HierarchyTest\TestAllowedChildrenB::class,
                    HierarchyTest\TestAllowedChildrenC::class,
                    HierarchyTest\TestAllowedChildrenD::class,
                    HierarchyTest\TestAllowedChildrenE::class,
                    HierarchyTest\TestAllowedChildrenCext::class,
                ],
            ],
            'directly sets allowed child' => [
                'className' => HierarchyTest\TestAllowedChildrenA::class,
                'expected' => [
                    HierarchyTest\TestAllowedChildrenB::class,
                ],
            ],
            'subclasses are allowed implicitly' => [
                'className' => HierarchyTest\TestAllowedChildrenB::class,
                'expected' => [
                    HierarchyTest\TestAllowedChildrenC::class,
                    HierarchyTest\TestAllowedChildrenCext::class,
                ],
            ],
            'multiple classes can be defined' => [
                'className' => HierarchyTest\TestAllowedChildrenC::class,
                'expected' => [
                    HierarchyTest\TestAllowedChildrenA::class,
                    HierarchyTest\TestAllowedChildrenD::class,
                ],
            ],
            'overrides (rather than merging with) parent class config' => [
                'className' => HierarchyTest\TestAllowedChildrenCext::class,
                'expected' => [
                    HierarchyTest\TestAllowedChildrenB::class,
                ],
            ],
            'explicitly excludes subclasses of the allowed child' => [
                'className' => HierarchyTest\TestAllowedChildrenD::class,
                'expected' => [
                    HierarchyTest\TestAllowedChildrenC::class,
                ],
            ],
            'explicitly allows no children' => [
                'className' => HierarchyTest\TestAllowedChildrenE::class,
                'expected' => [],
            ],
        ];
    }

    /**
     * Tests that various types of SiteTree classes will or will not be returned from the allowedChildren method
     */
    #[DataProvider('provideAllowedChildren')]
    public function testAllowedChildren(string $className, array $expected): void
    {
        $class = new $className();
        $this->assertSame($expected, $class->allowedChildren());
    }

    public static function provideValidationAllowedChildren(): array
    {
        return [
            'Does allow children on unrestricted parent' => [
                'parentClass' => HierarchyTest\HierarchyModel::class,
                'validateClass' => HierarchyTest\TestAllowedChildrenB::class,
                'expected' => true,
            ],
            'Does allow child specifically allowed by parent' => [
                'parentClass' => HierarchyTest\TestAllowedChildrenA::class,
                'validateClass' => HierarchyTest\TestAllowedChildrenB::class,
                'expected' => true,
            ],
            'Doesnt allow child on parents specifically restricting children' => [
                'parentClass' => HierarchyTest\TestAllowedChildrenC::class,
                'validateClass' => HierarchyTest\TestAllowedChildrenB::class,
                'expected' => false,
            ],
            'Doesnt allow child on parents disallowing all children' => [
                'parentClass' => HierarchyTest\TestAllowedChildrenE::class,
                'validateClass' => HierarchyTest\TestAllowedChildrenB::class,
                'expected' => false,
            ],
            'Does allow subclasses of allowed children by default' => [
                'parentClass' => HierarchyTest\TestAllowedChildrenB::class,
                'validateClass' => HierarchyTest\TestAllowedChildrenCext::class,
                'expected' => true,
            ],
            'Doesnt allow child where only parent class is allowed on parent node, and asterisk prefixing is used' => [
                'parentClass' => HierarchyTest\TestAllowedChildrenD::class,
                'validateClass' => HierarchyTest\TestAllowedChildrenCext::class,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidationAllowedChildren')]
    public function testValidationAllowedChildren(string $parentClass, string $validateClass, bool $expected): void
    {
        $parent = new $parentClass();
        $parent->write();
        $toValidate = new $validateClass();
        $toValidate->ParentID = $parent->ID;

        $this->assertSame($expected, $toValidate->validate()->isValid());
    }

    public static function provideValidationCanBeRoot(): array
    {
        return [
            [
                'canBeRoot' => true,
                'hasParent' => true,
                'expected' => true,
            ],
            [
                'canBeRoot' => true,
                'hasParent' => false,
                'expected' => true,
            ],
            [
                'canBeRoot' => false,
                'hasParent' => true,
                'expected' => true,
            ],
            [
                'canBeRoot' => false,
                'hasParent' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidationCanBeRoot')]
    public function testValidationCanBeRoot(bool $canBeRoot, bool $hasParent, bool $expected): void
    {
        $record = new HierarchyTest\HierarchyModel();
        if ($hasParent) {
            $parent = new HierarchyTest\HierarchyModel();
            $parent->write();
            $record->ParentID = $parent->ID;
        }

        HierarchyTest\HierarchyModel::config()->set('can_be_root', $canBeRoot);
        $this->assertSame($expected, $record->validate()->isValid());
    }

    /**
     * Test that duplicateWithChildren() works on models with no sort field
     */
    public function testDuplicateWithChildren(): void
    {
        $parent = new HierarchyTest\HierarchyModel();
        $parent->Title = 'Parent';
        $parent->write();

        $child1 = new HierarchyTest\HierarchyModel();
        $child1->ParentID = $parent->ID;
        $child1->Title = 'Child 1';
        $child1->write();

        $child2 = new HierarchyTest\HierarchyModel();
        $child2->ParentID = $parent->ID;
        $child2->Title = 'Child 2';
        $child2->write();

        $duplicateParent = $parent->duplicateWithChildren();
        $duplicateChildren = $duplicateParent->AllChildren()->toArray();
        $this->assertCount(2, $duplicateChildren);

        $duplicateChild1 = array_shift($duplicateChildren);
        $duplicateChild2 = array_shift($duplicateChildren);

        // Kept titles, but have new IDs
        $this->assertEquals($child1->Title, $duplicateChild1->Title);
        $this->assertEquals($child2->Title, $duplicateChild2->Title);
        $this->assertNotEquals($duplicateChild1->ID, $child1->ID);
        $this->assertNotEquals($duplicateChild2->ID, $child2->ID);
    }

    /**
     * Test that duplicateWithChildren() works on models which do have a sort field
     */
    public function testDuplicateWithChildrenRetainSort(): void
    {
        $parent = new HierarchyTest\SortableHierarchyModel();
        $parent->Title = 'Parent';
        $parent->write();

        $child1 = new HierarchyTest\SortableHierarchyModel();
        $child1->ParentID = $parent->ID;
        $child1->Title = 'Child 1';
        $child1->Sort = 2;
        $child1->write();

        $child2 = new HierarchyTest\SortableHierarchyModel();
        $child2->ParentID = $parent->ID;
        $child2->Title = 'Child 2';
        $child2->Sort = 1;
        $child2->write();

        $duplicateParent = $parent->duplicateWithChildren();
        $duplicateChildren = $duplicateParent->AllChildren()->toArray();
        $this->assertCount(2, $duplicateChildren);

        $duplicateChild2 = array_shift($duplicateChildren);
        $duplicateChild1 = array_shift($duplicateChildren);

        // Kept titles, but have new IDs
        $this->assertEquals($child1->Title, $duplicateChild1->Title);
        $this->assertEquals($child2->Title, $duplicateChild2->Title);
        $this->assertNotEquals($duplicateChild1->ID, $child1->ID);
        $this->assertNotEquals($duplicateChild2->ID, $child2->ID);

        // assertGreaterThan works by having the LOWER value first
        $this->assertGreaterThan($duplicateChild2->Sort, $duplicateChild1->Sort);
    }

    public static function provideDefaultChild(): array
    {
        return [
            'defaults to first allowed child' => [
                'class' => HierarchyTest\HierarchyModel::class,
                'defaultChildConfig' => null,
                'expected' => HierarchyTest\HierarchyModel::class,
            ],
            'respects default_child config' => [
                'class' => HierarchyTest\HierarchyModel::class,
                'defaultChildConfig' => HierarchyTest\TestAllowedChildrenA::class,
                'expected' => HierarchyTest\TestAllowedChildrenA::class,
            ],
            'doesnt allow children outside of class hierarchy' => [
                'class' => HierarchyTest\HierarchyModel::class,
                'defaultChildConfig' => HierarchyTest\SortableHierarchyModel::class,
                'expected' => HierarchyTest\HierarchyModel::class,
            ],
            'doesnt allow hidden children' => [
                'class' => HierarchyTest\HierarchyModel::class,
                'defaultChildConfig' => HierarchyTest\TestAllowedChildrenHidden::class,
                'expected' => HierarchyTest\HierarchyModel::class,
            ],
            'doesnt allow children that arent in allow list' => [
                'class' => HierarchyTest\TestAllowedChildrenA::class,
                'defaultChildConfig' => HierarchyTest\TestAllowedChildrenA::class,
                'expected' => HierarchyTest\TestAllowedChildrenB::class,
            ],
        ];
    }

    #[DataProvider('provideDefaultChild')]
    public function testDefaultChild(string $class, ?string $defaultChildConfig, ?string $expected): void
    {
        Config::forClass($class)->set('default_child', $defaultChildConfig);
        /** @var DataObject&Hierarchy $obj */
        $obj = new $class();

        $this->assertSame($expected, $obj->defaultChild());
    }

    public static function provideDefaultParent(): array
    {
        // These are subject to change but the current behaviour is very naive
        // so that's what we're validating against here
        return [
            'no default value' => [
                'class' => HierarchyTest\HierarchyModel::class,
                'defaultParentConfig' => null,
                'expected' => null,
            ],
            'respects default_parent config' => [
                'class' => HierarchyTest\HierarchyModel::class,
                'defaultParentConfig' => HierarchyTest\TestAllowedChildrenA::class,
                'expected' => HierarchyTest\TestAllowedChildrenA::class,
            ],
            'doesnt validate if the class is in our hierarchy' => [
                'class' => HierarchyTest\SortableHierarchyModel::class,
                'defaultParentConfig' => HierarchyTest\HierarchyModel::class,
                'expected' => HierarchyTest\HierarchyModel::class,
            ],
            'doesnt validate against allowedChildren of the parent class' => [
                'class' => HierarchyTest\TestAllowedChildrenA::class,
                'defaultParentConfig' => HierarchyTest\TestAllowedChildrenA::class,
                'expected' => HierarchyTest\TestAllowedChildrenA::class,
            ],
        ];
    }

    #[DataProvider('provideDefaultParent')]
    public function testDefaultParent(string $class, ?string $defaultParentConfig, ?string $expected): void
    {
        Config::forClass($class)->set('default_parent', $defaultParentConfig);
        /** @var DataObject&Hierarchy $obj */
        $obj = new $class();

        $this->assertSame($expected, $obj->defaultParent());
    }
}
