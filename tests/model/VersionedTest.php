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
		$page1 = Versioned::get_one_by_stage('Page', 'Stage', $page1->ID);
		
		$this->assertTrue($page1->Version > $changedVersion, 'Create a new higher version number');
		$this->assertEquals('orig', $page1->Content, 'Copies the content from the old version');
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
