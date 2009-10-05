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

	function testActionsPublishedRecord() {
		if(class_exists('SiteTreeCMSWorkflow')) return true;
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();
	
		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
	
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
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$pageID = $page->ID;
		$page->doPublish();
		$page->deleteFromStage('Stage');
		
		// Get the live version of the page
		$page = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		
		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
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
		
		$page = new Page();
		$page->CanEditType = 'LoggedInUsers';
		$page->write();
		$page->doPublish();
		$page->Content = 'Changed on Stage';
		$page->write();
		$page->flushCache();
		
		$author = $this->objFromFixture('Member', 'cmseditor');
		$this->session()->inst_set('loggedInAs', $author->ID);
		
		$actionsArr = $page->getCMSActions()->column('Name');
		
		$this->assertContains('action_save',$actionsArr);
		$this->assertContains('action_publish',$actionsArr);
		$this->assertContains('action_unpublish',$actionsArr);
		$this->assertContains('action_delete',$actionsArr);
		$this->assertNotContains('action_deletefromlive',$actionsArr);
		$this->assertContains('action_rollback',$actionsArr);
		$this->assertNotContains('action_revert',$actionsArr);
	}
}
?>