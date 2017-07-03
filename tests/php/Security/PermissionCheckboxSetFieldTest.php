<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionCheckboxSetField;
use SilverStripe\Dev\SapphireTest;

/**
 * @skipUpgrade
 */
class PermissionCheckboxSetFieldTest extends SapphireTest
{
    protected static $fixture_file = 'PermissionCheckboxSetFieldTest.yml';

    public function testHiddenPermissions()
    {
        $f = new PermissionCheckboxSetField(
            'Permissions',
            'Permissions',
            Permission::class,
            'GroupID'
        );
        $f->setHiddenPermissions(
            array('NON-ADMIN')
        );
        $this->assertEquals(
            $f->getHiddenPermissions(),
            array('NON-ADMIN')
        );
        $this->assertContains('ADMIN', $f->Field());
        $this->assertNotContains('NON-ADMIN', $f->Field());
    }

    public function testSaveInto()
    {
        /**
 * @var Group $group
*/
        $group = $this->objFromFixture(Group::class, 'group');  // tested group
        /**
 * @var Group $untouchable
*/
        $untouchable = $this->objFromFixture(Group::class, 'untouchable');  // group that should not change

        $field = new PermissionCheckboxSetField(
            'Permissions',
            'Permissions',
            Permission::class,
            'GroupID',
            $group
        );

        // get the number of permissions before we start
        $baseCount = DataObject::get(Permission::class)->count();

        // there are currently no permissions, save empty checkbox
        $field->saveInto($group);
        $group->flushCache();
        $untouchable->flushCache();
        $this->assertEquals($group->Permissions()->count(), 0, 'The tested group has no permissions');

        $this->assertEquals($untouchable->Permissions()->count(), 1, 'The other group has one permission');
        $this->assertEquals(
            $untouchable->Permissions()->where("\"Code\"='ADMIN'")->count(),
            1,
            'The other group has ADMIN permission'
        );

        $this->assertEquals(DataObject::get(Permission::class)->count(), $baseCount, 'There are no orphaned permissions');

        // add some permissions
        $field->setValue(
            array(
            'ADMIN'=>true,
            'NON-ADMIN'=>true
            )
        );

        $field->saveInto($group);
        $group->flushCache();
        $untouchable->flushCache();
        $this->assertEquals(
            $group->Permissions()->count(),
            2,
            'The tested group has two permissions permission'
        );
        $this->assertEquals(
            $group->Permissions()->where("\"Code\"='ADMIN'")->count(),
            1,
            'The tested group has ADMIN permission'
        );
        $this->assertEquals(
            $group->Permissions()->where("\"Code\"='NON-ADMIN'")->count(),
            1,
            'The tested group has CMS_ACCESS_AssetAdmin permission'
        );

        $this->assertEquals(
            $untouchable->Permissions()->count(),
            1,
            'The other group has one permission'
        );
        $this->assertEquals(
            $untouchable->Permissions()->where("\"Code\"='ADMIN'")->count(),
            1,
            'The other group has ADMIN permission'
        );

        $this->assertEquals(
            DataObject::get(Permission::class)->count(),
            $baseCount+2,
            'There are no orphaned permissions'
        );

        // remove permission
        $field->setValue(
            array(
            'ADMIN'=>true,
            )
        );

        $field->saveInto($group);
        $group->flushCache();
        $untouchable->flushCache();
        $this->assertEquals(
            $group->Permissions()->count(),
            1,
            'The tested group has 1 permission'
        );
        $this->assertEquals(
            $group->Permissions()->where("\"Code\"='ADMIN'")->count(),
            1,
            'The tested group has ADMIN permission'
        );

        $this->assertEquals(
            $untouchable->Permissions()->count(),
            1,
            'The other group has one permission'
        );
        $this->assertEquals(
            $untouchable->Permissions()->where("\"Code\"='ADMIN'")->count(),
            1,
            'The other group has ADMIN permission'
        );

        $this->assertEquals(
            DataObject::get(Permission::class)->count(),
            $baseCount+1,
            'There are no orphaned permissions'
        );
    }
}
