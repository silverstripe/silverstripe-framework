<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class MigrateSiteTreeLinkingTaskTest extends SapphireTest {
	
	public static $fixture_file = 'sapphire/tests/tasks/MigrateSiteTreeLinkingTaskTest.yml';
	
	public static $use_draft_site = true;
	
	public function testLinkingMigration() {
		ob_start();
		
		$task = new MigrateSiteTreeLinkingTask();
		$task->run(null);
		
		$this->assertEquals (
			"Rewrote 9 link(s) on 5 page(s) to use shortcodes.\n",
			ob_get_contents(),
			'Rewritten links are correctly reported'
		);
		ob_end_clean();
		
		$homeID   = $this->idFromFixture('SiteTree', 'home');
		$aboutID  = $this->idFromFixture('SiteTree', 'about');
		$staffID  = $this->idFromFixture('SiteTree', 'staff');
		$actionID = $this->idFromFixture('SiteTree', 'action');
		$hashID   = $this->idFromFixture('SiteTree', 'hash_link');
		
		$homeContent = sprintf (
			'<a href="[sitetree_link id=%d]">About</a><a href="[sitetree_link id=%d]">Staff</a><a href="http://silverstripe.org/">External Link</a>',
			$aboutID,
			$staffID
		);
		$aboutContent = sprintf (
			'<a href="[sitetree_link id=%d]">Home</a><a href="[sitetree_link id=%d]">Staff</a>',
			$homeID,
			$staffID
		);
		$staffContent = sprintf (
			'<a href="[sitetree_link id=%d]">Home</a><a href="[sitetree_link id=%d]">About</a>',
			$homeID,
			$aboutID
		);
		$actionContent = sprintf (
			'<a href="[sitetree_link id=%d]SearchForm">Search Form</a>', $homeID
		);
		$hashLinkContent = sprintf (
			'<a href="[sitetree_link id=%d]#anchor">Home</a><a href="[sitetree_link id=%d]#second-anchor">About</a>',
			$homeID,
			$aboutID
		);
		
		$this->assertEquals (
			$homeContent,
			DataObject::get_by_id('SiteTree', $homeID)->Content,
			'HTML URLSegment links are rewritten.'
		);
		$this->assertEquals (
			$aboutContent, 
			DataObject::get_by_id('SiteTree', $aboutID)->Content
		);
		$this->assertEquals (
			$staffContent,
			DataObject::get_by_id('SiteTree', $staffID)->Content
		);
		$this->assertEquals (
			$actionContent,
			DataObject::get_by_id('SiteTree', $actionID)->Content,
			'Links to actions on pages are rewritten correctly.'
		);
		$this->assertEquals (
			$hashLinkContent,
			DataObject::get_by_id('SiteTree', $hashID)->Content,
			'Hash/anchor links are correctly handled.'
		);
	}
	
}