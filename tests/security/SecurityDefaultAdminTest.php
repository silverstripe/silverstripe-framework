<?php
class SecurityDefaultAdminTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $defaultUsername = null;
	protected $defaultPassword = null;
	
	public function setUp() {
		parent::setUp();

		// TODO Workaround to force database clearing with no fixture present,
		// and avoid sideeffects from other tests
		if(!self::using_temp_db()) self::create_temp_db();
		self::empty_temp_db();

		$this->defaultUsername = Security::default_admin_username();
		$this->defaultPassword = Security::default_admin_password();
		Security::clear_default_admin();
		Security::setDefaultAdmin('admin', 'password');
		Permission::flush_permission_cache();
	}

	public function tearDown() {
		Security::setDefaultAdmin($this->defaultUsername, $this->defaultPassword);
		Permission::flush_permission_cache();
		parent::tearDown();
	}
	
	public function testCheckDefaultAdmin() {
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
	}

	public function testFindAnAdministratorCreatesNewUser() {
		$adminMembers = Permission::get_members_by_permission('ADMIN');
		$this->assertEquals(0, $adminMembers->count());

		$admin = Security::findAnAdministrator();

		$this->assertInstanceOf('Member', $admin);
		$this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
		$this->assertEquals($admin->Email, Security::default_admin_username());
		$this->assertNull($admin->Password);
	}

	public function testFindAnAdministratorWithoutDefaultAdmin() {
		// Clear default admin
		Security::clear_default_admin();

		$adminMembers = Permission::get_members_by_permission('ADMIN');
		$this->assertEquals(0, $adminMembers->count());

		$admin = Security::findAnAdministrator();

		$this->assertInstanceOf('Member', $admin);
		$this->assertTrue(Permission::checkMember($admin, 'ADMIN'));

		// User should be blank
		$this->assertEmpty($admin->Email);
		$this->assertEmpty($admin->Password);
	}

	public function testDefaultAdmin() {
		$adminMembers = Permission::get_members_by_permission('ADMIN');
		$this->assertEquals(0, $adminMembers->count());
		
		$admin = Member::default_admin();
		
		$this->assertInstanceOf('Member', $admin);
		$this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
		$this->assertEquals($admin->Email, Security::default_admin_username());
		$this->assertNull($admin->Password);

	}

}
