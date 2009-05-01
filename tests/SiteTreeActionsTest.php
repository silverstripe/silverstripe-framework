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
if(class_exists('SiteTreeCMSWorkflow')) {
	class SiteTreeActionsTest extends FunctionalTest {
		function testDummy() {}
	}	
} else {
	class SiteTreeActionsTest extends FunctionalTest {
	
		static $fixture_file = 'sapphire/tests/SiteTreeActionsTest.yml';
	
		function testActionsNewPage() {
			$className = 'Page';
			$page = new $className();
			$page->Title = 'New ' . $className;
			$page->URLSegment = "new-" . strtolower($className);
			$page->ClassName = $className;
			$page->ParentID = 0;
			$page->ID = 'new-Page-1';
		
			$author = $this->objFromFixture('Member', 'cmseditor');
			$this->session()->inst_set('loggedInAs', $author->ID);
		
			$actionsArr = $page->getCMSActions()->column('Name');
		
			$this->assertContains('action_save',$actionsArr);
			$this->assertContains('action_publish',$actionsArr);
			$this->assertNotContains('action_unpublish',$actionsArr);
			$this->assertContains('action_delete',$actionsArr);
			$this->assertNotContains('action_deletefromlive',$actionsArr);
			$this->assertNotContains('action_rollback',$actionsArr);
			$this->assertNotContains('action_revert',$actionsArr);
		}
	
		function testActionsPublishedRecord() {
			$page = new Page();
			$page->write();
			$page->publish('Stage', 'Live');
		
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
			$page = new Page();
			$page->write();
			$pageID = $page->ID;
			$page->publish('Stage', 'Live');
			$page->deleteFromStage('Stage');
			
			// Get the live version of the page
			$page = Versioned::get_one_by_stage("SiteTree", "Live", "`SiteTree`.ID = $pageID");
			
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
			$page = new Page();
			$page->write();
			$page->publish('Stage', 'Live');
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
}
?>