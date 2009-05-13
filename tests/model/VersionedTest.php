<?php

class VersionedTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/VersionedTest.yml';
	
	/**
	 * Test Versioned::get_including_deleted()
	 */
	function testGetIncludingDeleted() {
		// Delete a page
		$this->objFromFixture('Page', 'page3')->delete();

		// Get all items, ignoring deleted
		$remainingPages = DataObject::get("SiteTree", "ParentID = 0");
		// Check that page 3 has gone
		$this->assertNotNull($remainingPages);
		$this->assertEquals(array("Page 1", "Page 2"), $remainingPages->column('Title'));
		
		// Get all including deleted
		$allPages = Versioned::get_including_deleted("SiteTree", "ParentID = 0");
		// Check that page 3 is still there
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), $allPages->column('Title'));
		
		// Check that this still works if we switch to reading the other stage
		Versioned::reading_stage("Live");
		$allPages = Versioned::get_including_deleted("SiteTree", "ParentID = 0");
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), $allPages->column('Title'));
		
	}

}