<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionCheckboxSetField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * @skipUpgrade
 */
class PermissionTest extends SapphireTest
{

    protected static $fixture_file = 'PermissionTest.yml';

    public function testGetCodesGrouped()
    {
        $codes = Permission::get_codes();
        $this->assertArrayNotHasKey('SITETREE_VIEW_ALL', $codes);
    }

    public function testGetCodesUngrouped()
    {
        $codes = Permission::get_codes(false);
        $this->assertArrayHasKey('SITETREE_VIEW_ALL', $codes);
    }

    public function testDirectlyAppliedPermissions()
    {
        $member = $this->objFromFixture(Member::class, 'author');
        $this->assertTrue(Permission::checkMember($member, "SITETREE_VIEW_ALL"));
    }

    public function testCMSAccess()
    {
        $members = Member::get()->byIDs($this->allFixtureIDs(Member::class));
        foreach ($members as $member) {
            $this->assertTrue(Permission::checkMember($member, 'CMS_ACCESS'));
            $this->assertTrue(Permission::checkMember($member, ['CMS_ACCESS', 'CMS_ACCESS_Security']));
            $this->assertTrue(Permission::checkMember($member, ['CMS_ACCESS_Security', 'CMS_ACCESS']));
        }

        $member = new Member();
        $member->update(
            [
            'FirstName' => 'No CMS',
            'Surname' => 'Access',
            'Email' => 'no-access@example.com',
            ]
        );
        $member->write();
        $this->assertFalse(Permission::checkMember($member, 'CMS_ACCESS'));
        $this->assertFalse(Permission::checkMember($member, ['CMS_ACCESS', 'CMS_ACCESS_Security']));
        $this->assertFalse(Permission::checkMember($member, ['CMS_ACCESS_Security', 'CMS_ACCESS']));
    }

    public function testLeftAndMainAccessAll()
    {
        //add user and group
        $member = $this->objFromFixture(Member::class, 'leftandmain');

        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
    }

    public function testPermissionAreInheritedFromOneRole()
    {
        $member = $this->objFromFixture(Member::class, 'author');
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
        $this->assertFalse(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
    }

    public function testPermissionAreInheritedFromMultipleRoles()
    {
        $member = $this->objFromFixture(Member::class, 'access');
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
        $this->assertTrue(Permission::checkMember($member, "EDIT_PERMISSIONS"));
        $this->assertFalse(Permission::checkMember($member, "SITETREE_VIEW_ALL"));
    }

    public function testPermissionsForMember()
    {
        $member = $this->objFromFixture(Member::class, 'access');
        $permissions = Permission::permissions_for_member($member->ID);
        $this->assertEquals(4, count($permissions));
        $this->assertTrue(in_array('CMS_ACCESS_MyAdmin', $permissions));
        $this->assertTrue(in_array('CMS_ACCESS_AssetAdmin', $permissions));
        $this->assertTrue(in_array('CMS_ACCESS_SecurityAdmin', $permissions));
        $this->assertTrue(in_array('EDIT_PERMISSIONS', $permissions));

        $group = $this->objFromFixture("SilverStripe\\Security\\Group", "access");

        Permission::deny($group->ID, "CMS_ACCESS_MyAdmin");
        $permissions = Permission::permissions_for_member($member->ID);
        $this->assertEquals(3, count($permissions));
        $this->assertFalse(in_array('CMS_ACCESS_MyAdmin', $permissions));
    }

    public function testRolesAndPermissionsFromParentGroupsAreInherited()
    {
        $member = $this->objFromFixture(Member::class, 'globalauthor');

        // Check that permissions applied to the group are there
        $this->assertTrue(Permission::checkMember($member, "SITETREE_EDIT_ALL"));

        // Check that roles from parent groups are there
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
        $this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));

        // Check that permissions from parent groups are there
        $this->assertTrue(Permission::checkMember($member, "SITETREE_VIEW_ALL"));

        // Check that a random permission that shouldn't be there isn't
        $this->assertFalse(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
    }
    /**
     * Ensure the the get_*_by_permission functions are permission role aware
     */
    public function testGettingMembersByPermission()
    {
        $accessMember = $this->objFromFixture(Member::class, 'access');
        $accessAuthor = $this->objFromFixture(Member::class, 'author');

        $result = Permission::get_members_by_permission(['CMS_ACCESS_SecurityAdmin']);
        $resultIDs = $result ? $result->column() : [];

        $this->assertContains(
            $accessMember->ID,
            $resultIDs,
            'Member is found via a permission attached to a role'
        );
        $this->assertNotContains($accessAuthor->ID, $resultIDs);
    }


    public function testHiddenPermissions()
    {
        $permissionCheckboxSet = new PermissionCheckboxSetField('Permissions', 'Permissions', Permission::class, 'GroupID');
        $this->assertContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());

        Config::modify()->merge(Permission::class, 'hidden_permissions', ['CMS_ACCESS_LeftAndMain']);

        $this->assertNotContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());

        Config::inst()->remove(Permission::class, 'hidden_permissions');
        $this->assertContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());
    }

    public function testEmptyMemberFails()
    {
        $member = new Member();
        $this->assertFalse($member->exists());

        $this->logInWithPermission('ADMIN');

        $this->assertFalse(Permission::checkMember($member, 'ADMIN'));
        $this->assertFalse(Permission::checkMember($member, 'CMS_ACCESS_LeftAndMain'));
    }
}
