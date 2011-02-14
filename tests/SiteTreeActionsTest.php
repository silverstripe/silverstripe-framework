<?php
/**
 * Possible actions:
 * - action_save
 * - action_publish
 * - action_unpublish
 * - action_delete
 * - action_deletefromlive
 * - action_rollback
 * - action_revert
 * 
 * @package sapphire
 * @subpackage tests
 */
class SiteTreeActionsTest extends FunctionalTest {

	static $fixture_file = 'sapphire/tests/SiteTreeActionsTest.yml';
	
	static function set_up_once() {
		SiteTreeTest::set_up_once();

		parent::set_up_once();
	}
	
	static function tear_down_once() {
		SiteTreeTest::tear_down_once();
		
		parent::tear_down_once();
	}
	
	function testActionsReadonly() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;
		
		$readonlyEditor = $this->objFromFixture('Member', 'cmsreadonlyeditor');
		$this->session()->inst_set('loggedInAs', $readonlyEditor->ID);
	
		$page = new SiteTreeActionsTest_Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();
	
		$actionsArr = $page->getCMSActions()->column('Name');
	
		$this->assertNotContains('action_save',$actionsArr);
		$this->assertNotContains('action_publish',$actionsArr);
		$this->assertNotContains('action_unpublish',$actionsArr);
		$this->assertNotContains('action_delete',$actionsArr);
		$this->assertNotContains('action_deletefromlive',$actionsArr);
		$this->assertNotContains('action_rollback',$actionsArr);
		$this->assertNotContains('action_revert',$actionsArr);
	}
	
	function testActionsNoDeletePublishedRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;

		$this->logInWithPermission('ADMIN');
		
		$page = new SiteTreeActionsTest_Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$pageID = $page->ID;
		$page->doPublish();
		$page->deleteFromStage('Stage');
		
		// Get the live version of the page
		$page = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertType("SiteTree", $page);
		
		// Check that someone without the right permission can't delete the page
		$editor = $this->objFromFixture('Member', 'cmsnodeleteeditor');
		$this->session()->inst_set('loggedInAs', $editor->ID);

		$actionsArr = $page->getCMSActions()->column('Name');
		$this->assertNotContains('action_deletefromlive',$actionsArr);

		// Check that someone with the right permission can delete the page
 		$this->objFromFixture('Member', 'cmseditor')->logIn();
		$actionsArr = $page->getCMSActions()->column('Name');
		$this->assertContains('action_deletefromlive',$actionsArr);
	}

	function testActionsPublishedRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;

		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();

		$actionsArr = $page->getCMSActions()->column('Name');
	
		$this->assertContains('action_save',$actionsArr);
		$this->assertContains('action_publish',$actionsArr);
		$this->assertContains('action_unpublish',$actionsArr);
		$this->assertContains('action_delete',$actionsArr);
		$this->assertNotContains('action_deletefromlive',$actionsArr);
		$this->assertNotContains('action_rollback',$actionsArr);
		$this->assertNotContains('action_revert',$actionsArr);
	}
	
	function testActionsDeletedFromStageRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;

		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$pageID = $page->ID;
		$page->doPublish();
		$page->deleteFromStage('Stage');
		
		// Get the live version of the page
		$page = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertType('SiteTree', $page);
		
		$actionsArr = $page->getCMSActions()->column('Name');
		
		$this->assertNotContains('action_save',$actionsArr);
		$this->assertNotContains('action_publish',$actionsArr);
		$this->assertNotContains('action_unpublish',$actionsArr);
		$this->assertNotContains('action_delete',$actionsArr);
		$this->assertContains('action_deletefromlive',$actionsArr);
		$this->assertNotContains('action_rollback',$actionsArr);
		$this->assertContains('action_revert',$actionsArr);
	}
	
	function testActionsChangedOnStageRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;
		
		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();
		$page->Content = 'Changed on Stage';
		$page->write();
		$page->flushCache();
		
		$actionsArr = $page->getCMSActions()->column('Name');
		
		$this->assertContains('action_save',$actionsArr);
		$this->assertContains('action_publish',$actionsArr);
		$this->assertContains('action_unpublish',$actionsArr);
		$this->assertContains('action_delete',$actionsArr);
		$this->assertNotContains('action_deletefromlive',$actionsArr);
		$this->assertContains('action_rollback',$actionsArr);
		$this->assertNotContains('action_revert',$actionsArr);
	}

	function testActionsViewingOldVersion() {
		$p = new Page();
		$p->Content = 'test page first version';
		$p->write();
		$p->Content = 'new content';
		$p->write();

		// Looking at the old version, the ability to rollback to that version is available
		$version = DB::query('SELECT "Version" FROM "SiteTree_versions" WHERE "Content" = \'test page first version\'')->value();
		$old = Versioned::get_version('Page', $p->ID, $version);
		$actions = $old->getCMSActions()->column('Name');
		$this->assertNotContains('action_save', $actions);
		$this->assertNotContains('action_publish', $actions);
		$this->assertNotContains('action_unpublish', $actions);
		$this->assertNotContains('action_delete', $actions);
		$this->assertContains('action_email', $actions);
		$this->assertContains('action_rollback', $actions);
	}

}

class SiteTreeActionsTest_Page extends Page implements TestOnly {
	function canEdit($member = null) {
		return Permission::checkMember($member, 'SiteTreeActionsTest_Page_CANEDIT');
	}
	
	function canDelete($member = null) {
		return Permission::checkMember($member, 'SiteTreeActionsTest_Page_CANDELETE');
	}
}