<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class PermissionCheckboxSetFieldTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/security/PermissionCheckboxSetFieldTest.yml';
	
	function testHiddenPermissions() {
		$f = new PermissionCheckboxSetField(
			'Permissions',
			'Permissions',
			'Permission',
			'GroupID'
		);
		$f->setHiddenPermissions(
			array('CMS_ACCESS_ReportAdmin')
		);
		$this->assertEquals(
			$f->getHiddenPermissions(),
			array('CMS_ACCESS_ReportAdmin')
		);
		$this->assertContains('CMS_ACCESS_CMSMain', $f->Field());
		$this->assertNotContains('CMS_ACCESS_ReportAdmin', $f->Field());
	}
	
	function testSaveInto() {
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
		$this->assertEquals($untouchable->Permissions("\"Code\"='ADMIN'")->Count(), 1, 'The other group has ADMIN permission');

		$this->assertEquals(DataObject::get('Permission')->Count(), $baseCount, 'There are no orphaned permissions');
				
		// add some permissions
		$field->setValue(array(
			'ADMIN'=>true,
			'CMS_ACCESS_AssetAdmin'=>true
		));

		$field->saveInto($group);
		$group->flushCache();
		$untouchable->flushCache();
		$this->assertEquals($group->Permissions()->Count(), 2, 'The tested group has two permissions permission');
		$this->assertEquals($group->Permissions("\"Code\"='ADMIN'")->Count(), 1, 'The tested group has ADMIN permission');
		$this->assertEquals($group->Permissions("\"Code\"='CMS_ACCESS_AssetAdmin'")->Count(), 1, 'The tested group has CMS_ACCESS_AssetAdmin permission');

		$this->assertEquals($untouchable->Permissions()->Count(), 1, 'The other group has one permission');
		$this->assertEquals($untouchable->Permissions("\"Code\"='ADMIN'")->Count(), 1, 'The other group has ADMIN permission');

		$this->assertEquals(DataObject::get('Permission')->Count(), $baseCount+2, 'There are no orphaned permissions');
		
		// remove permission
		$field->setValue(array(
			'ADMIN'=>true,
		));

		$field->saveInto($group);
		$group->flushCache();
		$untouchable->flushCache();
		$this->assertEquals($group->Permissions()->Count(), 1, 'The tested group has 1 permission');
		$this->assertEquals($group->Permissions("\"Code\"='ADMIN'")->Count(), 1, 'The tested group has ADMIN permission');

		$this->assertEquals($untouchable->Permissions()->Count(), 1, 'The other group has one permission');
		$this->assertEquals($untouchable->Permissions("\"Code\"='ADMIN'")->Count(), 1, 'The other group has ADMIN permission');

		$this->assertEquals(DataObject::get('Permission')->Count(), $baseCount+1, 'There are no orphaned permissions');
	}
}