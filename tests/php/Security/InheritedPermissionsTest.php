<?php

namespace SilverStripe\Security\Tests;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionChecker;
use SilverStripe\Security\Test\InheritedPermissionsTest\TestPermissionNode;
use SilverStripe\Security\Test\InheritedPermissionsTest\TestDefaultPermissionChecker;
use SilverStripe\Versioned\Versioned;
use ReflectionClass;

class InheritedPermissionsTest extends SapphireTest
{
    protected static $fixture_file = 'InheritedPermissionsTest.yml';

    protected static $extra_dataobjects = [
        TestPermissionNode::class,
    ];

    /**
     * @var TestDefaultPermissionChecker
     */
    protected $rootPermissions = null;

    protected function setUp()
    {
        parent::setUp();
        // Register root permissions
        $permission = InheritedPermissions::create(TestPermissionNode::class)
            ->setGlobalEditPermissions(['TEST_NODE_ACCESS'])
            ->setDefaultPermissions($this->rootPermissions = new TestDefaultPermissionChecker());
        Injector::inst()->registerService(
            $permission,
            PermissionChecker::class.'.testpermissions'
        );

        // Reset root permission
    }

    public function testEditPermissions()
    {
        $editor = $this->objFromFixture(Member::class, 'editor');

        $about = $this->objFromFixture(TestPermissionNode::class, 'about');
        $aboutStaff = $this->objFromFixture(TestPermissionNode::class, 'about-staff');
        $history = $this->objFromFixture(TestPermissionNode::class, 'history');
        $products = $this->objFromFixture(TestPermissionNode::class, 'products');
        $product1 = $this->objFromFixture(TestPermissionNode::class, 'products-product1');
        $product4 = $this->objFromFixture(TestPermissionNode::class, 'products-product4');

        // Test logged out users cannot edit
        Member::actAs(null, function () use ($aboutStaff) {
            $this->assertFalse($aboutStaff->canEdit());
        });

        // Can't edit a page that is locked to admins
        $this->assertFalse($about->canEdit($editor));

        // Can edit a page that is locked to editors
        $this->assertTrue($products->canEdit($editor));

        // Can edit a child of that page that inherits
        $this->assertTrue($product1->canEdit($editor));

        // Can't edit a child of that page that has its permissions overridden
        $this->assertFalse($product4->canEdit($editor));

        // Test that root node respects root permissions
        $this->assertTrue($history->canEdit($editor));

        TestPermissionNode::getInheritedPermissions()->clearCache();
        $this->rootPermissions->setCanEdit(false);

        // With root edit false, permissions are now denied for CanEditType = Inherit
        $this->assertFalse($history->canEdit($editor));
    }

    public function testDeletePermissions()
    {
        $editor = $this->objFromFixture(Member::class, 'editor');

        $about = $this->objFromFixture(TestPermissionNode::class, 'about');
        $aboutStaff = $this->objFromFixture(TestPermissionNode::class, 'about-staff');
        $history = $this->objFromFixture(TestPermissionNode::class, 'history');
        $products = $this->objFromFixture(TestPermissionNode::class, 'products');
        $product1 = $this->objFromFixture(TestPermissionNode::class, 'products-product1');
        $product4 = $this->objFromFixture(TestPermissionNode::class, 'products-product4');

        // Test logged out users cannot edit
        Member::actAs(null, function () use ($aboutStaff) {
            $this->assertFalse($aboutStaff->canDelete());
        });

        // Can't edit a page that is locked to admins
        $this->assertFalse($about->canDelete($editor));

        // Can't delete a page if a child (product4) is un-deletable
        $this->assertFalse($products->canDelete($editor));

        // Can edit a child of that page that inherits
        $this->assertTrue($product1->canDelete($editor));

        // Can't edit a child of that page that has its permissions overridden
        $this->assertFalse($product4->canDelete($editor));

        // Test that root node respects root permissions
        $this->assertTrue($history->canDelete($editor));

        TestPermissionNode::getInheritedPermissions()->clearCache();
        $this->rootPermissions->setCanEdit(false);

        // With root edit false, permissions are now denied for CanEditType = Inherit
        $this->assertFalse($history->canDelete($editor));
    }

    public function testViewPermissions()
    {
        $history = $this->objFromFixture(TestPermissionNode::class, 'history');
        $contact = $this->objFromFixture(TestPermissionNode::class, 'contact');
        $contactForm = $this->objFromFixture(TestPermissionNode::class, 'contact-form');
        $secret = $this->objFromFixture(TestPermissionNode::class, 'secret');
        $secretNested = $this->objFromFixture(TestPermissionNode::class, 'secret-nested');
        $protected = $this->objFromFixture(TestPermissionNode::class, 'protected');
        $protectedChild = $this->objFromFixture(TestPermissionNode::class, 'protected-child');
        $editor = $this->objFromFixture(Member::class, 'editor');

        // Not logged in user can only access Inherit or Anyone pages
        Member::actAs(
            null,
            function () use ($protectedChild, $secretNested, $protected, $secret, $history, $contact, $contactForm) {
                $this->assertTrue($history->canView());
                $this->assertTrue($contact->canView());
                $this->assertTrue($contactForm->canView());
                // Protected
                $this->assertFalse($secret->canView());
                $this->assertFalse($secretNested->canView());
                $this->assertFalse($protected->canView());
                $this->assertFalse($protectedChild->canView());
            }
        );

        // Editor can view pages restricted to logged in users
        $this->assertTrue($secret->canView($editor));
        $this->assertTrue($secretNested->canView($editor));

        // Cannot read admin-only pages
        $this->assertFalse($protected->canView($editor));
        $this->assertFalse($protectedChild->canView($editor));

        // Check root permissions
        $this->assertTrue($history->canView($editor));

        TestPermissionNode::getInheritedPermissions()->clearCache();
        $this->rootPermissions->setCanView(false);

        $this->assertFalse($history->canView($editor));
    }

    /**
     * Test that draft permissions deny unrestricted live permissions
     */
    public function testRestrictedDraftUnrestrictedLive()
    {
        Versioned::set_stage(Versioned::DRAFT);

        // Should be editable by non-admin editor
        /** @var TestPermissionNode $products */
        $products = $this->objFromFixture(TestPermissionNode::class, 'products');
        /** @var TestPermissionNode $products1 */
        $products1 = $this->objFromFixture(TestPermissionNode::class, 'products-product1');
        $editor = $this->objFromFixture(Member::class, 'editor');

        // Ensure the editor can edit
        $this->assertTrue($products->canEdit($editor));
        $this->assertTrue($products1->canEdit($editor));

        // Write current version to live
        $products->writeToStage(Versioned::LIVE);
        $products1->writeToStage(Versioned::LIVE);

        // Draft version restrict to admins
        $products->EditorGroups()->setByIDList([
            $this->idFromFixture(Group::class, 'admins')
        ]);
        $products->write();

        // Ensure editor can no longer edit
        TestPermissionNode::getInheritedPermissions()->clearCache();
        $this->assertFalse($products->canEdit($editor));
        $this->assertFalse($products1->canEdit($editor));
    }

    /**
     * Test that draft permissions permit access over live permissions
     */
    public function testUnrestrictedDraftOverridesLive()
    {
        Versioned::set_stage(Versioned::DRAFT);

        // Should be editable by non-admin editor
        /** @var TestPermissionNode $about */
        $about = $this->objFromFixture(TestPermissionNode::class, 'about');
        /** @var TestPermissionNode $aboutStaff */
        $aboutStaff = $this->objFromFixture(TestPermissionNode::class, 'about-staff');
        $editor = $this->objFromFixture(Member::class, 'editor');

        // Ensure the editor can't edit
        $this->assertFalse($about->canEdit($editor));
        $this->assertFalse($aboutStaff->canEdit($editor));

        // Write current version to live
        $about->writeToStage(Versioned::LIVE);
        $aboutStaff->writeToStage(Versioned::LIVE);

        // Unrestrict draft
        $about->CanEditType = InheritedPermissions::LOGGED_IN_USERS;
        $about->write();

        // Ensure editor can no longer edit
        TestPermissionNode::getInheritedPermissions()->clearCache();
        $this->assertTrue($about->canEdit($editor));
        $this->assertTrue($aboutStaff->canEdit($editor));
    }

    /**
     * Ensure that flipping parent / child relationship on live doesn't
     * cause infinite loop
     */
    public function testMobiusHierarchy()
    {
        Versioned::set_stage(Versioned::DRAFT);

        /** @var TestPermissionNode $history */
        $history = $this->objFromFixture(TestPermissionNode::class, 'history');
        /** @var TestPermissionNode $historyGallery */
        $historyGallery = $this->objFromFixture(TestPermissionNode::class, 'history-gallery');

        // Publish current state to live
        $history->writeToStage(Versioned::LIVE);
        $historyGallery->writeToStage(Versioned::LIVE);

        // Flip relation
        $historyGallery->ParentID = 0;
        $historyGallery->write();
        $history->ParentID = $historyGallery->ID;
        $history->write();

        // Test viewability (not logged in users)
        Member::actAs(null, function () use ($history, $historyGallery) {
            $this->assertTrue($history->canView());
            $this->assertTrue($historyGallery->canView());
        });

        // Change permission on draft root and ensure it affects both
        $historyGallery->CanViewType = InheritedPermissions::LOGGED_IN_USERS;
        $historyGallery->write();
        TestPermissionNode::getInheritedPermissions()->clearCache();

        // Test viewability (not logged in users)
        Member::actAs(null, function () use ($history, $historyGallery) {
            $this->assertFalse($historyGallery->canView());
            $this->assertFalse($history->canView());
        });
    }

    public function testPermissionsPersistCache()
    {
        $cache = Injector::inst()->create(CacheInterface::class . '.InheritedPermissions');
        $member = $this->objFromFixture(Member::class, 'editor');

        /** @var TestPermissionNode $history */
        $history = $this->objFromFixture(TestPermissionNode::class, 'history');
        /** @var TestPermissionNode $historyGallery */
        $historyGallery = $this->objFromFixture(TestPermissionNode::class, 'history-gallery');
        $permissionChecker = new InheritedPermissions(TestPermissionNode::class, $cache);

        $viewKey = $this->generateCacheKey($permissionChecker, InheritedPermissions::VIEW, $member->ID);
        $editKey = $this->generateCacheKey($permissionChecker, InheritedPermissions::EDIT, $member->ID);

        $this->assertNull($cache->get($editKey));
        $this->assertNull($cache->get($viewKey));

        $permissionChecker->canEditMultiple([$history->ID, $historyGallery->ID], $member);
        $this->assertNull($cache->get($editKey));
        $this->assertNull($cache->get($viewKey));

        unset($permissionChecker);
        $this->assertTrue(is_array($cache->get($editKey)));
        $this->assertNull($cache->get($viewKey));
        $this->assertArrayHasKey($history->ID, $cache->get($editKey));
        $this->assertArrayHasKey($historyGallery->ID, $cache->get($editKey));

        $permissionChecker = new InheritedPermissions(TestPermissionNode::class, $cache);
        $permissionChecker->canViewMultiple([$history->ID], $member);
        $this->assertNotNull($cache->get($editKey));
        $this->assertNull($cache->get($viewKey));

        unset($permissionChecker);
        $this->assertTrue(is_array($cache->get($viewKey)));
        $this->assertTrue(is_array($cache->get($editKey)));
        $this->assertArrayHasKey($history->ID, $cache->get($viewKey));
        $this->assertArrayNotHasKey($historyGallery->ID, $cache->get($viewKey));
    }

    public function testPermissionsFlushCache()
    {
        $cache = Injector::inst()->create(CacheInterface::class . '.InheritedPermissions');
        $permissionChecker = new InheritedPermissions(TestPermissionNode::class, $cache);
        $member1 = $this->objFromFixture(Member::class, 'editor');
        $member2 = $this->objFromFixture(Member::class, 'admin');
        $editKey1 = $this->generateCacheKey($permissionChecker, InheritedPermissions::EDIT, $member1->ID);
        $editKey2 = $this->generateCacheKey($permissionChecker, InheritedPermissions::EDIT, $member2->ID);
        $viewKey1 = $this->generateCacheKey($permissionChecker, InheritedPermissions::VIEW, $member1->ID);
        $viewKey2 = $this->generateCacheKey($permissionChecker, InheritedPermissions::VIEW, $member2->ID);

        foreach([$editKey1, $editKey2, $viewKey1, $viewKey2] as $key) {
            $this->assertNull($cache->get($key));
        }

        /** @var TestPermissionNode $history */
        $history = $this->objFromFixture(TestPermissionNode::class, 'history');
        /** @var TestPermissionNode $historyGallery */
        $historyGallery = $this->objFromFixture(TestPermissionNode::class, 'history-gallery');

        $permissionChecker->canEditMultiple([$history->ID, $historyGallery->ID], $member1);
        $permissionChecker->canViewMultiple([$history->ID, $historyGallery->ID], $member1);
        $permissionChecker->canEditMultiple([$history->ID, $historyGallery->ID], $member2);
        $permissionChecker->canViewMultiple([$history->ID, $historyGallery->ID], $member2);

        unset($permissionChecker);

        foreach([$editKey1, $editKey2, $viewKey1, $viewKey2] as $key) {
            $this->assertNotNull($cache->get($key));
        }
        $permissionChecker = new InheritedPermissions(TestPermissionNode::class, $cache);

        // Non existent ID
        $permissionChecker->flushCache('dummy');
        foreach([$editKey1, $editKey2, $viewKey1, $viewKey2] as $key) {
            $this->assertNotNull($cache->get($key));
        }

        // Precision strike
        $permissionChecker->flushCache([$member1->ID]);
        // Member1 should be clear
        $this->assertNull($cache->get($editKey1));
        $this->assertNull($cache->get($viewKey1));
        // Member 2 is unaffected
        $this->assertNotNull($cache->get($editKey2));
        $this->assertNotNull($cache->get($viewKey2));

        // Nuclear
        $permissionChecker->flushCache();
        foreach([$editKey1, $editKey2, $viewKey1, $viewKey2] as $key) {
            $this->assertNull($cache->get($key));
        }

    }

    protected function generateCacheKey(InheritedPermissions $inst, $type, $memberID)
    {
        $reflection = new ReflectionClass(InheritedPermissions::class);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        return $method->invokeArgs($inst, [$type, $memberID]);
    }

}
