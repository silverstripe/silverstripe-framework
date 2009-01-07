<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SiteTreeTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/SiteTreeTest.yml';
	
	/**
	 * Test generation of the URLSegment values.
	 *  - Turns things into lowercase-hyphen-format
	 *  - Generates from Title by default, unless URLSegment is explicitly set
	 *  - Resolves duplicates by appending a number
	 *  - renames classes with a class name conflict
	 */
	function testURLGeneration() {
		$expectedURLs = array(
			'home' => 'home',
			'staff' => 'my-staff',
			'about' => 'about-us',
			'staffduplicate' => 'my-staff-2',
			'product1' => '1-1-test-product',
			'product2' => 'another-product',
			'product3' => 'another-product-2',
			'product4' => 'another-product-3',
			'object'   => 'object',
			'controller' => 'controller-2'
		);
		
		foreach($expectedURLs as $fixture => $urlSegment) {
			$obj = $this->fixture->objFromFixture('Page', $fixture);
			$this->assertEquals($urlSegment, $obj->URLSegment);
		}
	}
	
	/**
	 * Test that publication copies data to SiteTree_Live
	 */
	function testPublishCopiesToLiveTable() {
		$obj = $this->fixture->objFromFixture('Page','about');
		$obj->publish('Stage', 'Live');
		
		$createdID = DB::query("SELECT ID FROM SiteTree_Live WHERE URLSegment = '$obj->URLSegment'")->value();
		$this->assertEquals($obj->ID, $createdID);
	}
	
	function testParentNodeCachedInMemory() {
		$parent = new SiteTree();
     	$parent->Title = 'Section Title';
     	$child = new SiteTree();
     	$child->Title = 'Page Title';
		$child->setParent($parent);
		
		$this->assertType("SiteTree", $child->Parent);
		$this->assertEquals("Section Title", $child->Parent->Title);
	}
	
	function testParentModelReturnType() {
		$parent = new SiteTreeTest_PageNode();
		$child = new SiteTreeTest_PageNode();

		$child->setParent($parent);
		$this->assertType('SiteTreeTest_PageNode', $child->Parent);
	}
	
	/**
	 * Confirm that DataObject::get_one() gets records from SiteTree_Live
	 */
	function testGetOneFromLive() {
		$s = new SiteTree();
		$s->Title = "V1";
		$s->URLSegment = "get-one-test-page";
		$s->write();
		$s->publish("Stage", "Live");
		$s->Title = "V2";
		$s->write();
		
		$oldStage = Versioned::current_stage();
		Versioned::reading_stage('Live');
		
		$checkSiteTree = DataObject::get_one("SiteTree", "URLSegment = 'get-one-test-page'");
		$this->assertEquals("V1", $checkSiteTree->Title);
	}
	
	function testChidrenOfRootAreTopLevelPages() {
		$pages = DataObject::get("SiteTree");
		foreach($pages as $page) $page->publish('Stage', 'Live');
		unset($pages);
		
		/* If we create a new SiteTree object with ID = 0 */
		$obj = new SiteTree();
		/* Then its children should be the top-level pages */
		$stageChildren = $obj->stageChildren()->toDropDownMap('ID','Title');
		$liveChildren = $obj->liveChildren()->toDropDownMap('ID','Title');
		$allChildren = $obj->AllChildrenIncludingDeleted()->toDropDownMap('ID','Title');
		
		$this->assertContains('Home', $stageChildren);
		$this->assertContains('Products', $stageChildren);
		$this->assertNotContains('Staff', $stageChildren);

		$this->assertContains('Home', $liveChildren);
		$this->assertContains('Products', $liveChildren);
		$this->assertNotContains('Staff', $liveChildren);

		$this->assertContains('Home', $allChildren);
		$this->assertContains('Products', $allChildren);
		$this->assertNotContains('Staff', $allChildren);
	}

	function testCanSaveBlankToHasOneRelations() {
		/* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
		$page = new SiteTree();
		$parentID = $this->idFromFixture('Page', 'home');
		$page->ParentID = $parentID;
		$page->write();
		$this->assertEquals($parentID, DB::query("SELECT ParentID FROM SiteTree WHERE ID = $page->ID")->value());

		/* You should then be able to save a null/0/'' value to the relation */
		$page->ParentID = null;
		$page->write();
		$this->assertEquals(0, DB::query("SELECT ParentID FROM SiteTree WHERE ID = $page->ID")->value());
	}
	
	function testStageStates() {
		// newly created page
		$createdPage = new SiteTree();
		$createdPage->write();
		$this->assertFalse($createdPage->IsDeletedFromStage);
		$this->assertTrue($createdPage->IsAddedToStage);
		$this->assertTrue($createdPage->IsModifiedOnStage);
		
		// published page 
		$publishedPage = new SiteTree();
		$publishedPage->write();
		$publishedPage->publish('Stage','Live');
		$this->assertFalse($publishedPage->IsDeletedFromStage);
		$this->assertFalse($publishedPage->IsAddedToStage);
		$this->assertFalse($publishedPage->IsModifiedOnStage); 
		
		// published page, deleted from stage
		$deletedFromDraftPage = new SiteTree();
		$deletedFromDraftPage->write();
		$deletedFromDraftPageID = $deletedFromDraftPage->ID;
		$deletedFromDraftPage->publish('Stage','Live');
		$deletedFromDraftPage->deleteFromStage('Stage');
		$this->assertTrue($deletedFromDraftPage->IsDeletedFromStage);
		$this->assertFalse($deletedFromDraftPage->IsAddedToStage);
		$this->assertFalse($deletedFromDraftPage->IsModifiedOnStage);
		
		// published page, deleted from live
		$deletedFromLivePage = new SiteTree();
		$deletedFromLivePage->write();
		$deletedFromLivePage->publish('Stage','Live');
		$deletedFromLivePage->deleteFromStage('Stage');
		$deletedFromLivePage->deleteFromStage('Live');
		$this->assertTrue($deletedFromLivePage->IsDeletedFromStage);
		$this->assertFalse($deletedFromLivePage->IsAddedToStage);
		$this->assertFalse($deletedFromLivePage->IsModifiedOnStage);
		
		// published page, modified
		$modifiedOnDraftPage = new SiteTree();
		$modifiedOnDraftPage->write();
		$modifiedOnDraftPage->publish('Stage','Live');
		$modifiedOnDraftPage->Content = 'modified';
		$modifiedOnDraftPage->write();
		$this->assertFalse($modifiedOnDraftPage->IsDeletedFromStage);
		$this->assertFalse($modifiedOnDraftPage->IsAddedToStage);
		$this->assertTrue($modifiedOnDraftPage->IsModifiedOnStage);
	}
}

// We make these extend page since that's what all page types are expected to do
class SiteTreeTest_PageNode extends Page implements TestOnly { }
class SiteTreeTest_PageNode_Controller extends Page_Controller implements TestOnly { 
}

?>