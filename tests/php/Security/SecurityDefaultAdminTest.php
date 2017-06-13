<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Service\DefaultAdminService;

class SecurityDefaultAdminTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected $defaultUsername = null;
    protected $defaultPassword = null;

    protected function setUp()
    {
        parent::setUp();

        // TODO Workaround to force database clearing with no fixture present,
        // and avoid sideeffects from other tests
        if (!self::using_temp_db()) {
            self::create_temp_db();
        }
        self::empty_temp_db();

        $this->defaultUsername = Security::default_admin_username();
        $this->defaultPassword = Security::default_admin_password();
        Security::clear_default_admin();
        Security::setDefaultAdmin('admin', 'password');
        Permission::reset();
    }

    protected function tearDown()
    {
        Security::clear_default_admin();
        Security::setDefaultAdmin($this->defaultUsername, $this->defaultPassword);
        Permission::reset();
        parent::tearDown();
    }

    public function testCheckDefaultAdmin()
    {
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

    public function testFindAnAdministratorCreatesNewUser()
    {
        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = Security::findAnAdministrator();

        $this->assertInstanceOf(Member::class, $admin);
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
        $this->assertEquals($admin->Email, Security::default_admin_username());
        $this->assertNull($admin->Password);
    }

    public function testFindAnAdministratorWithoutDefaultAdmin()
    {
        $service = Injector::inst()->get(DefaultAdminService::class);
        // Clear default admin
        DefaultAdminService::clearDefaultAdmin();

        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = $service->findOrCreateDefaultAdmin();

        $this->assertNull($admin);
        // When clearing the admin, it will not re-instate it anymore
        DefaultAdminService::setDefaultAdmin('admin', 'password');
        $admin = $service->findAnAdministrator();
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));

        // User should be blank
        $this->assertEmpty($admin->Email);
        $this->assertEmpty($admin->Password);
    }

    public function testDefaultAdmin()
    {
        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = Member::default_admin();

        $this->assertInstanceOf(Member::class, $admin);
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
        $this->assertEquals($admin->Email, Security::default_admin_username());
        $this->assertNull($admin->Password);
    }
}
