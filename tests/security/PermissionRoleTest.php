<?php
/**
 * @package framework
 * @subpackage tests
 */
class PermissionRoleTest extends FunctionalTest {
	static $fixture_file = 'PermissionRoleTest.yml';
	
	function testDelete() {
		$role = $this->objFromFixture('PermissionRole', 'role');
		
		$role->delete();
		
		$this->assertEquals(0, DataObject::get('PermissionRole', "\"ID\"={$role->ID}")->count(), 'Role is removed');
		$this->assertEquals(0, DataObject::get('PermissionRoleCode',"\"RoleID\"={$role->ID}")->count(), 'Permissions removed along with the role');
	}
}
