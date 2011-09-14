<?php
/**
 * @package cms
 * @subpackage tests
 */
class SecurityAdminTest extends FunctionalTest {

	static $fixture_file = 'sapphire/admin/tests/LeftAndMainTest.yml';
	
	protected $extraDataObjects = array('LeftAndMainTest_Object');
	
	function testGroupExport() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		/* First, open the applicable group */
		$this->get('admin/security/show/' . $this->idFromFixture('Group','admin'));
		$this->assertRegExp('/<input[^>]+id="Form_EditForm_Title"[^>]+value="Administrators"[^>]*>/',$this->content());
		
		/* Then load the export page */
		$this->get('admin/security/EditForm/field/Members/export');
		$lines = preg_split('/\n/', $this->content());

		$this->assertEquals(count($lines), 3, "Export with members has one content row");
		$this->assertRegExp('/"","","admin@example.com"/', $lines[1], "Member values are correctly exported");
	}

	function testEmptyGroupExport() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		/* First, open the applicable group */
		$this->get('admin/security/show/' . $this->idFromFixture('Group','empty'));
		$this->assertRegExp('/<input[^>]+id="Form_EditForm_Title"[^>]+value="Empty Group"[^>]*>/',$this->content());
		
		/* Then load the export page */
		$this->get('admin/security/EditForm/field/Members/export');
		$lines = preg_split('/\n/', $this->content());
		
		$this->assertEquals(count($lines), 2, "Empty export only has header fields and an empty row");
		$this->assertEquals($lines[1], '', "Empty export only has no content row");
	}
	
	function testAddHiddenPermission() {
		SecurityAdmin::add_hidden_permission('CMS_ACCESS_ReportAdmin');
		$this->assertContains('CMS_ACCESS_ReportAdmin', SecurityAdmin::get_hidden_permissions());
		
		// reset to defaults
		SecurityAdmin::clear_hidden_permissions();
	}
	
	function testRemoveHiddenPermission() {
		SecurityAdmin::add_hidden_permission('CMS_ACCESS_ReportAdmin');
		$this->assertContains('CMS_ACCESS_ReportAdmin', SecurityAdmin::get_hidden_permissions());
		SecurityAdmin::remove_hidden_permission('CMS_ACCESS_ReportAdmin');
		$this->assertNotContains('CMS_ACCESS_ReportAdmin', SecurityAdmin::get_hidden_permissions());
		
		// reset to defaults
		SecurityAdmin::clear_hidden_permissions();
	}
	
	function testClearHiddenPermission() {
		SecurityAdmin::add_hidden_permission('CMS_ACCESS_ReportAdmin');
		$this->assertContains('CMS_ACCESS_ReportAdmin', SecurityAdmin::get_hidden_permissions());
		SecurityAdmin::clear_hidden_permissions('CMS_ACCESS_ReportAdmin');
		$this->assertNotContains('CMS_ACCESS_ReportAdmin', SecurityAdmin::get_hidden_permissions());
	}
	
	function testPermissionFieldRespectsHiddenPermissions() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));
		
		$group = $this->objFromFixture('Group', 'admin');
		
		SecurityAdmin::add_hidden_permission('CMS_ACCESS_ReportAdmin');
		$response = $this->get('admin/security/show/' . $group->ID);
		
		$this->assertContains(
			'CMS_ACCESS_SecurityAdmin',
			$response->getBody()
		);
		$this->assertNotContains(
			'CMS_ACCESS_ReportAdmin',
			$response->getBody()
		);
		
		// reset to defaults
		SecurityAdmin::clear_hidden_permissions();
	}
}

?>