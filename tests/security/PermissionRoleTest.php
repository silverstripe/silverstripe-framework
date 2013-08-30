<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class PermissionRoleTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/security/PermissionRoleTest.yml';
	
	function testDelete() {
		$role = $this->objFromFixture('PermissionRole', 'role');
		
		$role->delete();
		
		$this->assertNull(DataObject::get('PermissionRole', "\"ID\"={$role->ID}"), 'Role is removed');
		$this->assertNull(DataObject::get('PermissionRoleCode',"\"RoleID\"={$role->ID}"), 'Permissions removed along with the role');
	}

	public function testValidatesPrivilegedPermissions() {
		$nonAdminCode = new PermissionRoleCode(array('Code' => 'CMS_ACCESS_CMSMain'));
		$nonAdminValidateMethod = new ReflectionMethod($nonAdminCode, 'validate');
		$nonAdminValidateMethod->setAccessible(true);

		$adminCode = new PermissionRoleCode(array('Code' => 'ADMIN'));
		$adminValidateMethod = new ReflectionMethod($adminCode, 'validate');
		$adminValidateMethod->setAccessible(true);

		$this->logInWithPermission('APPLY_ROLES');
		$result = $nonAdminValidateMethod->invoke($nonAdminCode);
		$this->assertTrue(
			$result->valid(),
			'Members with only APPLY_ROLES can create non-privileged permission role codes'
		);

		$this->logInWithPermission('APPLY_ROLES');
		$result = $adminValidateMethod->invoke($adminCode);
		$this->assertFalse(
			$result->valid(),
			'Members with only APPLY_ROLES can\'t create privileged permission role codes'
		);

		$this->logInWithPermission('ADMIN');
		$result = $adminValidateMethod->invoke($adminCode);
		$this->assertTrue(
			$result->valid(),
			'Members with ADMIN can create privileged permission role codes'
		);
	}
}
