<?php
class SecurityDefaultAdminTest extends SapphireTest {
	
	function setUp() {
		parent::setUp();

		// TODO Workaround to force database clearing with no fixture present, 
		// and avoid sideeffects from other tests
		if(!self::using_temp_db()) self::create_temp_db();
		self::empty_temp_db();
	}
	
	function testCheckDefaultAdmin() {
		// TODO There's currently no way to inspect default admin state,
		//  hence we don't override existing settings
		if(Security::has_default_admin()) return;
		
		Security::setDefaultAdmin('admin', 'password');
		
		$this->assertTrue(Security::has_default_admin());
		$this->assertTrue(
			Security::check_default_admin('admin', 'password'),
			'Succeeds with correct username and password'
		);
		$this->assertFalse(
			Security::check_default_admin('wronguser', 'password'),
			'Fails with incorrect username'
		);
		$this->assertFalse(
			Security::check_default_admin('admin', 'wrongpassword'),
			'Fails with incorrect password'
		);
		
		Security::setDefaultAdmin(null, null);
	}
	
	function testFindAnAdministratorCreatesNewUser() {
		$adminMembers = Permission::get_members_by_permission('ADMIN');
		$this->assertEquals(0, $adminMembers->count());
		
		$admin = Security::findAnAdministrator();
		
		$this->assertInstanceOf('Member', $admin);
		$this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
		$this->assertNull($admin->Email);
		$this->assertNull($admin->Password);
	}
	
}
