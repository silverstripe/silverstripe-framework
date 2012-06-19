<?php

class PermissionTest extends SapphireTest {
	static $fixture_file = 'PermissionTest.yml';
	
	function testGetCodesGrouped() {
		$codes = Permission::get_codes();
		$this->assertArrayNotHasKey('SITETREE_VIEW_ALL', $codes);
	}
	
	function testGetCodesUngrouped() {
		$codes = Permission::get_codes(null, false);
		$this->assertArrayHasKey('SITETREE_VIEW_ALL', $codes);
	}
		
	function testDirectlyAppliedPermissions() {
		$member = $this->objFromFixture('Member', 'author');
		$this->assertTrue(Permission::checkMember($member, "SITETREE_VIEW_ALL"));
	}
	
	function testPermissionAreInheritedFromOneRole() {
		$member = $this->objFromFixture('Member', 'author');
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
		$this->assertFalse(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
	}
	
	function testPermissionAreInheritedFromMultipleRoles() {
		$member = $this->objFromFixture('Member', 'access');
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_MyAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_AssetAdmin"));
		$this->assertTrue(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin"));
		$this->assertTrue(Permission::checkMember($member, "EDIT_PERMISSIONS"));
		$this->assertFalse(Permission::checkMember($member, "SITETREE_VIEW_ALL"));
	}
	
	function testRolesAndPermissionsFromParentGroupsAreInherited() {
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
	function testGettingMembersByPermission() {
		$accessMember = $this->objFromFixture('Member', 'access');
		$accessAuthor = $this->objFromFixture('Member', 'author');

		$result = Permission::get_members_by_permission(array('CMS_ACCESS_SecurityAdmin'));
		$resultIDs = $result ? $result->column() : array();
		
		$this->assertContains($accessMember->ID, $resultIDs,
			'Member is found via a permission attached to a role');
		$this->assertNotContains($accessAuthor->ID, $resultIDs);
	}

	
	function testHiddenPermissions(){
		$permissionCheckboxSet = new PermissionCheckboxSetField('Permissions','Permissions','Permission','GroupID');
		$this->assertContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());
		
		Permission::add_to_hidden_permissions('CMS_ACCESS_LeftAndMain');

		$this->assertNotContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());
		
		Permission::remove_from_hidden_permissions('CMS_ACCESS_LeftAndMain');
		$this->assertContains('CMS_ACCESS_LeftAndMain', $permissionCheckboxSet->Field());
	}	
}
