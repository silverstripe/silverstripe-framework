<?php
/**
 * @package framework
 * @subpackage tests
 */
class PermissionCheckboxSetFieldTest extends SapphireTest {
	protected static $fixture_file = 'PermissionCheckboxSetFieldTest.yml';

	public function testHiddenPermissions() {
		$f = new PermissionCheckboxSetField(
			'Permissions',
			'Permissions',
			'Permission',
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

	public function testSaveInto() {
		$group = $this->objFromFixture('Group', 'group');  // tested group
		$untouchable = $this->objFromFixture('Group', 'untouchable');  // group that should not change

		$field = new PermissionCheckboxSetField(
			'Permissions',
			'Permissions',
			'Permission',
			'GroupID',
			$group
		);

		// get the number of permissions before we start
		$baseCount = DataObject::get('Permission')->Count();

		// there are currently no permissions, save empty checkbox
		$field->saveInto($group);
		$group->flushCache();
		$untouchable->flushCache();
		$this->assertEquals($group->Permissions()->Count(), 0, 'The tested group has no permissions');

		$this->assertEquals($untouchable->Permissions()->Count(), 1, 'The other group has one permission');
		$this->assertEquals($untouchable->Permissions()->where("\"Code\"='ADMIN'")->Count(), 1,
			'The other group has ADMIN permission');

		$this->assertEquals(DataObject::get('Permission')->Count(), $baseCount, 'There are no orphaned permissions');

		// add some permissions
		$field->setValue(array(
			'ADMIN'=>true,
			'NON-ADMIN'=>true
		));

		$field->saveInto($group);
		$group->flushCache();
		$untouchable->flushCache();
		$this->assertEquals($group->Permissions()->Count(), 2,
			'The tested group has two permissions permission');
		$this->assertEquals($group->Permissions()->where("\"Code\"='ADMIN'")->Count(), 1,
			'The tested group has ADMIN permission');
		$this->assertEquals($group->Permissions()->where("\"Code\"='NON-ADMIN'")->Count(), 1,
			'The tested group has CMS_ACCESS_AssetAdmin permission');

		$this->assertEquals($untouchable->Permissions()->Count(), 1,
			'The other group has one permission');
		$this->assertEquals($untouchable->Permissions()->where("\"Code\"='ADMIN'")->Count(), 1,
			'The other group has ADMIN permission');

		$this->assertEquals(DataObject::get('Permission')->Count(), $baseCount+2,
			'There are no orphaned permissions');

		// remove permission
		$field->setValue(array(
			'ADMIN'=>true,
		));

		$field->saveInto($group);
		$group->flushCache();
		$untouchable->flushCache();
		$this->assertEquals($group->Permissions()->Count(), 1,
			'The tested group has 1 permission');
		$this->assertEquals($group->Permissions()->where("\"Code\"='ADMIN'")->Count(), 1,
			'The tested group has ADMIN permission');

		$this->assertEquals($untouchable->Permissions()->Count(), 1,
			'The other group has one permission');
		$this->assertEquals($untouchable->Permissions()->where("\"Code\"='ADMIN'")->Count(), 1,
			'The other group has ADMIN permission');

		$this->assertEquals(DataObject::get('Permission')->Count(), $baseCount+1,
			'There are no orphaned permissions');
	}
}
