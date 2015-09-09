<?php

/**
 * @package framework
 * @subpackage tests
 */
class PermissionTest extends SapphireTest {

	protected static $fixture_file = 'PermissionTest.yml';

	public function testGetCodesGrouped() {
		$codes = Permission::get_codes();
		$this->assertArrayNotHasKey('SITETREE_VIEW_ALL', $codes);
	}

	public function testGetCodesUngrouped() {
		$codes = Permission::get_codes(false);
		$this->assertArrayHasKey('SITETREE_VIEW_ALL', $codes);
	}

	public function testDirectlyAppliedPermissions() {
		$member = $this->objFromFixture('Member', 'author');
		$this->assertTrue(Permission::checkMember($member, "SITETREE_VIEW_ALL"));
	}

	public function testCMSAccess() {
		$members = Member::get()->byIDs($this->allFixtureIDs('Member'));
		foreach ($members as $member) {
			$this->assertTrue(Permission::checkMember($member, 'CMS_ACCESS'));
		}

		$member = new Member();
		$member->update(array(
			'FirstName' => 'No CMS',
			'Surname' => 'Access',
			'Email' => 'no-access@example.com',
		));
		$member->write();
		$this->assertFalse(Permission::checkMember($member, 'CMS_ACCESS'));
	}

	public function testLeftAndMainAccessAll() {
		//add user and group
		$member = $this->objFromFixture('Member', 'leftandmain');

		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
	}
	
	public function testPermissionAreInheritedFromOneRole() {
		$member = $this->objFromFixture('Member', 'author');
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
		$this->assertFalse(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
	}

	public function testPermissionAreInheritedFromMultipleRoles() {
		$member = $this->objFromFixture('Member', 'access');
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
		$this->assertTrue(Permission::checkMember($member, "EDIT_PERMISSIONS"));
		$this->assertFalse(Permission::checkMember($member, "SITETREE_VIEW_ALL"));
	}

	public function testPermissionsForMember() {
		$member = $this->objFromFixture('Member', 'access');
		$permissions = Permission::permissions_for_member($member->ID);
		$this->assertEquals(4, count($permissions));
		$this->assertTrue(in_array('CMS_ACCESS_MyAdmin', $permissions));
		$this->assertTrue(in_array('CMS_ACCESS_AssetAdmin', $permissions));
		$this->assertTrue(in_array('CMS_ACCESS_SecurityAdmin', $permissions));
		$this->assertTrue(in_array('EDIT_PERMISSIONS', $permissions));

		$group = $this->objFromFixture("Group", "access");

		Permission::deny($group->ID, "CMS_ACCESS_MyAdmin");
		$permissions = Permission::permissions_for_member($member->ID);
		$this->assertEquals(3, count($permissions));
		$this->assertFalse(in_array('CMS_ACCESS_MyAdmin', $permissions));
	}

	public function testRolesAndPermissionsFromParentGroupsAreInherited() {
		$member = $this->objFromFixture('Member', 'globalauthor');

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
	public function testGettingMembersByPermission() {
		$accessMember = $this->objFromFixture('Member', 'access');
		$accessAuthor = $this->objFromFixture('Member', 'author');

		$result = Permission::get_members_by_permission(array('CMS_ACCESS_SecurityAdmin'));
		$resultIDs = $result ? $result->column() : array();

		$this->assertContains($accessMember->ID, $resultIDs,
			'Member is found via a permission attached to a role');
		$this->assertNotContains($accessAuthor->ID, $resultIDs);
	}


	public function testHiddenPermissions(){
		$permissionCheckboxSet = new PermissionCheckboxSetField('Permissions','Permissions','Permission','GroupID');
		$this->assertContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());

		Config::inst()->update('Permission', 'hidden_permissions', array('CMS_ACCESS_LeftAndMain'));

		$this->assertNotContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());

		Config::inst()->remove('Permission', 'hidden_permissions');
		$this->assertContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());
	}
}
