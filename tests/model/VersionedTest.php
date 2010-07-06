<?php

class VersionedTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/VersionedTest.yml';

	protected $extraDataObjects = array(
		'VersionedTest_DataObject',
	);
	
	function testForceChangeUpdatesVersion() {
		$obj = new VersionedTest_DataObject();
		$obj->Name = "test";
		$obj->write();
		
		$oldVersion = $obj->Version;
		$obj->forceChange();
		$obj->write();
	
		$this->assertTrue(
			($obj->Version > $oldVersion),
			"A object Version is increased when just calling forceChange() without any other changes"
		);
	}
	
	/**
	 * Test Versioned::get_including_deleted()
	 */
	function testGetIncludingDeleted() {
		// Delete a page
		$this->objFromFixture('Page', 'page3')->delete();
	
		// Get all items, ignoring deleted
		$remainingPages = DataObject::get("SiteTree", "\"ParentID\" = 0", "\"SiteTree\".\"ID\" ASC");
		// Check that page 3 has gone
		$this->assertNotNull($remainingPages);
		$this->assertEquals(array("Page 1", "Page 2"), $remainingPages->column('Title'));
		
		// Get all including deleted
		$allPages = Versioned::get_including_deleted("SiteTree", "\"ParentID\" = 0", "\"SiteTree\".\"ID\" ASC");
		// Check that page 3 is still there
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), $allPages->column('Title'));
		
		// Check that this still works if we switch to reading the other stage
		Versioned::reading_stage("Live");
		$allPages = Versioned::get_including_deleted("SiteTree", "\"ParentID\" = 0", "\"SiteTree\".\"ID\" ASC");
		$this->assertEquals(array("Page 1", "Page 2", "Page 3"), $allPages->column('Title'));
		
	}
	
	function testVersionedFieldsAdded() {
		$obj = new VersionedTest_DataObject();
		// Check that the Version column is added as a full-fledged column
		$this->assertType('Int', $obj->dbObject('Version'));
	
		$obj2 = new VersionedTest_Subclass();
		// Check that the Version column is added as a full-fledged column
		$this->assertType('Int', $obj2->dbObject('Version'));
	}

	function testPublishCreateNewVersion() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$page1->Content = 'orig';
		$page1->write();
		$oldVersion = $page1->Version;
		$page1->publish('Stage', 'Live', false);
		$this->assertEquals($oldVersion, $page1->Version, 'publish() with $createNewVersion=FALSE');
		
		$page1->Content = 'changed';
		$page1->write();
		$oldVersion = $page1->Version;
		$page1->publish('Stage', 'Live', true);
		$this->assertTrue($oldVersion < $page1->Version, 'publish() with $createNewVersion=TRUE');
	}
	
	function testRollbackTo() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$page1->Content = 'orig';
		$page1->write();
		$page1->publish('Stage', 'Live');
		$origVersion = $page1->Version;
		
		$page1->Content = 'changed';
		$page1->write();
		$page1->publish('Stage', 'Live');
		$changedVersion = $page1->Version;

		$page1->doRollbackTo($origVersion);
		$page1 = Versioned::get_one_by_stage('Page', 'Stage', sprintf('"SiteTree"."ID" = %d', $page1->ID));
		
		$this->assertTrue($page1->Version > $changedVersion, 'Create a new higher version number');
		$this->assertEquals('orig', $page1->Content, 'Copies the content from the old version');
	}
	
	function testDeleteFromStage() {
		$page1 = $this->objFromFixture('Page', 'page1');
		$pageID = $page1->ID;
		
		$page1->Content = 'orig';
		$page1->write();
		$page1->publish('Stage', 'Live');
		
		$this->assertEquals(1, DB::query('SELECT COUNT(*) FROM "SiteTree" WHERE "ID" = '.$pageID)->value());
		$this->assertEquals(1, DB::query('SELECT COUNT(*) FROM "SiteTree_Live" WHERE "ID" = '.$pageID)->value());
		
		$page1->deleteFromStage('Live');
		
		// Confirm that deleteFromStage() doesn't manipulate the original record
		$this->assertEquals($pageID, $page1->ID);

		$this->assertEquals(1, DB::query('SELECT COUNT(*) FROM "SiteTree" WHERE "ID" = '.$pageID)->value());
		$this->assertEquals(0, DB::query('SELECT COUNT(*) FROM "SiteTree_Live" WHERE "ID" = '.$pageID)->value());

		$page1->delete();

		$this->assertEquals(0, $page1->ID);
		$this->assertEquals(0, DB::query('SELECT COUNT(*) FROM "SiteTree" WHERE "ID" = '.$pageID)->value());
		$this->assertEquals(0, DB::query('SELECT COUNT(*) FROM "SiteTree_Live" WHERE "ID" = '.$pageID)->value());
	}

	function testWritingNewToStage() {
		$origStage = Versioned::current_stage();
		
		Versioned::reading_stage("Stage");
		$page = new Page();
		$page->Title = "testWritingNewToStage";
		$page->URLSegment = "testWritingNewToStage";
		$page->write();
		
		$live = Versioned::get_by_stage('SiteTree', 'Live', "\"SiteTree_Live\".\"ID\"='$page->ID'");
		$this->assertNull($live);
		
		$stage = Versioned::get_by_stage('SiteTree', 'Stage', "\"SiteTree\".\"ID\"='$page->ID'");
		$this->assertNotNull($stage);
		$this->assertEquals($stage->First()->Title, 'testWritingNewToStage');
		
		Versioned::reading_stage($origStage);
	}

	/**
	 * This tests for the situation described in the ticket #5596. 
	 * Writing new Page to live first creates a row in SiteTree table (to get the new ID), then "changes
	 * it's mind" in Versioned and writes SiteTree_Live. It does not remove the SiteTree record though.
	 */ 
	function testWritingNewToLive() {
		$origStage = Versioned::current_stage();
		
		Versioned::reading_stage("Live");
		$page = new Page();
		$page->Title = "testWritingNewToLive";
		$page->URLSegment = "testWritingNewToLive";
		$page->write();
		
		$live = Versioned::get_by_stage('SiteTree', 'Live', "\"SiteTree_Live\".\"ID\"='$page->ID'");
		$this->assertNotNull($live->First());
		$this->assertEquals($live->First()->Title, 'testWritingNewToLive');
		
		$stage = Versioned::get_by_stage('SiteTree', 'Stage', "\"SiteTree\".\"ID\"='$page->ID'");
		$this->assertNull($stage);
		
		Versioned::reading_stage($origStage);
	}
}

class VersionedTest_DataObject extends DataObject implements TestOnly {
	static $db = array(
		"Name" => "Varchar",
	);

	static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
}

class VersionedTest_Subclass extends VersionedTest_DataObject implements TestOnly {
	static $db = array(
		"ExtraField" => "Varchar",
	);
}
