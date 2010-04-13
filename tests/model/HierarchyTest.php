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
	
		// Check numHistoricalChildren
		$this->assertEquals(3, singleton('Page')->numHistoricalChildren());

		// Check that both page 2 children are returned
		$page2 = $this->objFromFixture('Page', 'page2');
		$this->assertEquals(array("Page 2a", "Page 2b"), 
			$page2->AllHistoricalChildren()->column('Title'));

		// Check numHistoricalChildren
		$this->assertEquals(2, $page2->numHistoricalChildren());

			
		// Page 3 has been deleted; let's bring it back from the grave
		$page3 = Versioned::get_including_deleted("SiteTree", "\"Title\" = 'Page 3'")->First();
	
		// Check that both page 3 children are returned
		$this->assertEquals(array("Page 3a", "Page 3b"), 
			$page3->AllHistoricalChildren()->column('Title'));
			
		// Check numHistoricalChildren
		$this->assertEquals(2, $page3->numHistoricalChildren());
		
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
		$pages = DataObject::get("Page", '', '"ID" ASC');
		$marked = $expanded = array();
		foreach($pages as $page) {
			if($page->isMarked()) $marked[] = $page->Title;
			if($page->isExpanded()) $expanded[] = $page->Title;
		}
		
		$this->assertEquals(array('Page 2', 'Page 3', 'Page 2a', 'Page 2b'), $marked);
		$this->assertEquals(array('Page 2', 'Page 2a', 'Page 2b'), $expanded);
	}
	
	function testNumChildren() {
		$this->assertEquals($this->objFromFixture('Page', 'page1')->numChildren(), 0);
		$this->assertEquals($this->objFromFixture('Page', 'page2')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('Page', 'page3')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('Page', 'page2a')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('Page', 'page2b')->numChildren(), 0);
		$this->assertEquals($this->objFromFixture('Page', 'page3a')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('Page', 'page3b')->numChildren(), 0);
		
		$page1 = $this->objFromFixture('Page', 'page1');
		$this->assertEquals($page1->numChildren(), 0);
		$page1Child1 = new Page();
		$page1Child1->ParentID = $page1->ID;
		$page1Child1->write();
		$this->assertEquals($page1->numChildren(false), 1,
			'numChildren() caching can be disabled through method parameter'
		);
		$page1Child2 = new Page();
		$page1Child2->ParentID = $page1->ID;
		$page1Child2->write();
		$page1->flushCache();
		$this->assertEquals($page1->numChildren(), 2,
			'numChildren() caching can be disabled by flushCache()'
		);
	}

	function testLoadDescendantIDListIntoArray() {
		$page2 = $this->objFromFixture('Page', 'page2');
		$page2a = $this->objFromFixture('Page', 'page2a');
		$page2b = $this->objFromFixture('Page', 'page2b');
		$page2aa = $this->objFromFixture('Page', 'page2aa');
		$page2ab = $this->objFromFixture('Page', 'page2ab');
		
		$page2IdList = $page2->getDescendantIDList();
		$page2aIdList = $page2a->getDescendantIDList();
		
		$this->assertContains($page2a->ID, $page2IdList);
		$this->assertContains($page2b->ID, $page2IdList);
		$this->assertContains($page2aa->ID, $page2IdList);
		$this->assertContains($page2ab->ID, $page2IdList);
		$this->assertEquals(4, count($page2IdList));
		
		$this->assertContains($page2aa->ID, $page2aIdList);
		$this->assertContains($page2ab->ID, $page2aIdList);
		$this->assertEquals(2, count($page2aIdList));
	}

}