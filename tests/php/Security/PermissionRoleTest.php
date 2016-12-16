<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\PermissionRoleCode;
use SilverStripe\Dev\FunctionalTest;

class PermissionRoleTest extends FunctionalTest
{
    protected static $fixture_file = 'PermissionRoleTest.yml';

    public function testDelete()
    {
        $role = $this->objFromFixture(PermissionRole::class, 'role');

        $role->delete();

        $this->assertEquals(
            0,
            DataObject::get(PermissionRole::class, "\"ID\"={$role->ID}")->count(),
            'Role is removed'
        );
        $this->assertEquals(
            0,
            DataObject::get(PermissionRoleCode::class, "\"RoleID\"={$role->ID}")->count(),
            'Permissions removed along with the role'
        );
    }

    public function testValidatesPrivilegedPermissions()
    {
        $nonAdminCode = new PermissionRoleCode(array('Code' => 'CMS_ACCESS_CMSMain'));
        $adminCode = new PermissionRoleCode(array('Code' => 'ADMIN'));

        $this->logInWithPermission('APPLY_ROLES');
        $result = $nonAdminCode->validate();
        $this->assertTrue(
            $result->isValid(),
            'Members with only APPLY_ROLES can create non-privileged permission role codes'
        );

        $this->logInWithPermission('APPLY_ROLES');
        $result = $adminCode->validate();
        $this->assertFalse(
            $result->isValid(),
            'Members with only APPLY_ROLES can\'t create privileged permission role codes'
        );

        $this->logInWithPermission('ADMIN');
        $result = $adminCode->validate();
        $this->assertTrue(
            $result->isValid(),
            'Members with ADMIN can create privileged permission role codes'
        );
    }
}
