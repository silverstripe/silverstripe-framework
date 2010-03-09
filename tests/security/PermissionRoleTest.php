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
}
