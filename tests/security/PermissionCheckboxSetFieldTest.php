<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class PermissionCheckboxSetFieldTest extends SapphireTest {
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
}