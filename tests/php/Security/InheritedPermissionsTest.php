<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionChecker;
use SilverStripe\Security\Test\InheritedPermissionsTest\TestPermissionNode;
use SilverStripe\Security\Test\InheritedPermissionsTest\TestDefaultPermissionChecker;
use SilverStripe\Versioned\Versioned;

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
            PermissionChecker::class . '.testpermissions'
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
}
