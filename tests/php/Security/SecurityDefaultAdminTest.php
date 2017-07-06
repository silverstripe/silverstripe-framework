<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\DefaultAdminService;

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
        if (!static::$tempDB->isUsed()) {
            static::$tempDB->build();
        }
        static::$tempDB->clearAllData();

        if (DefaultAdminService::hasDefaultAdmin()) {
            $this->defaultUsername = DefaultAdminService::getDefaultAdminUsername();
            $this->defaultPassword = DefaultAdminService::getDefaultAdminPassword();
            DefaultAdminService::clearDefaultAdmin();
        } else {
            $this->defaultUsername = null;
            $this->defaultPassword = null;
        }
        DefaultAdminService::setDefaultAdmin('admin', 'password');
        Permission::reset();
    }

    protected function tearDown()
    {
        DefaultAdminService::clearDefaultAdmin();
        if ($this->defaultUsername) {
            DefaultAdminService::setDefaultAdmin($this->defaultUsername, $this->defaultPassword);
        }
        Permission::reset();
        parent::tearDown();
    }

    public function testCheckDefaultAdmin()
    {
        $this->assertTrue(DefaultAdminService::hasDefaultAdmin());
        $this->assertTrue(
            DefaultAdminService::isDefaultAdminCredentials('admin', 'password'),
            'Succeeds with correct username and password'
        );
        $this->assertFalse(
            DefaultAdminService::isDefaultAdminCredentials('wronguser', 'password'),
            'Fails with incorrect username'
        );
        $this->assertFalse(
            DefaultAdminService::isDefaultAdminCredentials('admin', 'wrongpassword'),
            'Fails with incorrect password'
        );
    }

    public function testFindAnAdministratorCreatesNewUser()
    {
        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();

        $this->assertInstanceOf(Member::class, $admin);
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
        $this->assertEquals($admin->Email, DefaultAdminService::getDefaultAdminUsername());
        $this->assertTrue(DefaultAdminService::isDefaultAdmin($admin->Email));
        $this->assertNull($admin->Password);
    }

    public function testFindOrCreateAdmin()
    {
        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = DefaultAdminService::singleton()->findOrCreateAdmin('newadmin@example.com', 'Admin Name');

        $this->assertInstanceOf(Member::class, $admin);
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
        $this->assertEquals('newadmin@example.com', $admin->Email);
        $this->assertEquals('Admin Name', $admin->FirstName);
        $this->assertNull($admin->Password);
    }

    public function testFindAnAdministratorWithoutDefaultAdmin()
    {
        // Clear default admin
        $service = DefaultAdminService::singleton();
        DefaultAdminService::clearDefaultAdmin();

        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = $service->findOrCreateDefaultAdmin();
        $this->assertNull($admin);

        // When clearing the admin, it will not re-instate it anymore
        DefaultAdminService::setDefaultAdmin('admin', 'password');
        $admin = $service->findOrCreateDefaultAdmin();
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));

        // User should have Email but no Password
        $this->assertEquals('admin', $admin->Email);
        $this->assertEmpty($admin->Password);
    }

    public function testDefaultAdmin()
    {
        $adminMembers = Permission::get_members_by_permission('ADMIN');
        $this->assertEquals(0, $adminMembers->count());

        $admin = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
        $this->assertInstanceOf(Member::class, $admin);
        $this->assertTrue(Permission::checkMember($admin, 'ADMIN'));
        $this->assertEquals($admin->Email, DefaultAdminService::getDefaultAdminUsername());
        $this->assertTrue(DefaultAdminService::isDefaultAdmin($admin->Email));
        $this->assertNull($admin->Password);
    }
}
