<?php

class SiteTreeBacklinksTest extends SapphireTest {
	static $fixture_file = "sapphire/tests/SiteTreeBacklinksTest.yml";
	
	static function set_up_once() {
		SiteTreeTest::set_up_once();

		parent::set_up_once();
	}
	
	static function tear_down_once() {
		SiteTreeTest::tear_down_once();
		
		parent::tear_down_once();
	}
	
	function setUp() {
		parent::setUp();
		
		// Log in as admin so that we don't run into permission issues.  That's not what we're
		// testing here.
		$this->logInWithPermission('ADMIN');
	}

	function testSavingPageWithLinkAddsBacklink() {
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 2 doesn't exist
		$page2 = $this->objFromFixture('Page', 'page2');
		$this->assertFalse($page1->BackLinkTracking()->containsIDs(array($page2->ID)), 'Assert backlink to page 2 doesn\'t exist');
		
		// add hyperlink to page 1 on page 2
		$page2->Content .= '<p><a href="page1/">Testing page 1 link</a></p>';
		$page2->write();
		
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 2 exists
		$this->assertTrue($page1->BackLinkTracking()->containsIDs(array($page2->ID)), 'Assert backlink to page 2 exists');
	}
	
	function testRemovingLinkFromPageRemovesBacklink() {
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 3 exits
		$page3 = $this->objFromFixture('Page', 'page3');
		$this->assertTrue($page1->BackLinkTracking()->containsIDs(array($page3->ID)), 'Assert backlink to page 3 exists');
		
		// remove hyperlink to page 1
		$page3->Content = '<p>No links anymore!</p>';
		$page3->write();
		
		// load page 1
		$page1 = $this->objFromFixture('Page', 'page1');
		
		// assert backlink to page 3 exists
		$this->assertFalse($page1->BackLinkTracking()->containsIDs(array($page3->ID)), 'Assert backlink to page 3 doesn\'t exist');
	}
}

?>
