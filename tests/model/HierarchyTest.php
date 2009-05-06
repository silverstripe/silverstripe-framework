<?php

class HierarchyTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/HierarchyTest.yml';

	/**
	 * Test Hierarchy::AllHistoricalChildren().
	 */
	function testAllHistoricalChildren() {
		// Delete some pages
		$this->objFromFixture('Page', 'page2b')->delete();
		$this->objFromFixture('Page', 'page3a')->delete();
		$this->objFromFixture('Page', 'page3')->delete();

		// Check that page1-3 appear at the top level of the AllHistoricalChildren tree
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), 
			singleton('Page')->AllHistoricalChildren()->column('Title'));

		// Check that both page 2 children are returned
		$page2 = $this->objFromFixture('Page', 'page2');
		$this->assertEquals(array("Page 2a", "Page 2b"), 
			$page2->AllHistoricalChildren()->column('Title'));
			
		// Page 3 has been deleted; let's bring it back from the grave
		$page3 = Versioned::get_including_deleted("SiteTree", "Title = 'Page 3'")->First();

		// Check that both page 3 children are returned
		$this->assertEquals(array("Page 3a", "Page 3b"), 
			$page3->AllHistoricalChildren()->column('Title'));
		
	}
	
	/**
	 * Test that you can call Hierarchy::markExpanded/Unexpanded/Open() on a page, and that
	 * calling Hierarchy::isMarked() on a different instance of that object will return true.
	 */
	function testItemMarkingIsntRestrictedToSpecificInstance() {
		// Mark a few pages
		$this->objFromFixture('Page', 'page2')->markExpanded();
		$this->objFromFixture('Page', 'page2a')->markExpanded();
		$this->objFromFixture('Page', 'page2b')->markExpanded();
		$this->objFromFixture('Page', 'page3')->markUnexpanded();
		
		// Query some pages in a different context and check their m
		$pages = DataObject::get("Page");
		$marked = $expanded = array();
		foreach($pages as $page) {
			if($page->isMarked()) $marked[] = $page->Title;
			if($page->isExpanded()) $expanded[] = $page->Title;
		}
		
		$this->assertEquals(array('Page 2', 'Page 3', 'Page 2a', 'Page 2b'), $marked);
		$this->assertEquals(array('Page 2', 'Page 2a', 'Page 2b'), $expanded);
		
	} 
	
}