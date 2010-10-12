<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SiteTreeBrokenLinksTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/SiteTreeBrokenLinksTest.yml';
	
	static function set_up_once() {
		SiteTreeTest::set_up_once();

		parent::set_up_once();
	}
	
	static function tear_down_once() {
		SiteTreeTest::tear_down_once();
		
		parent::tear_down_once();
	}

	function testBrokenLinksBetweenPages() {
		$obj = $this->objFromFixture('Page','content');
		
		$obj->Content = '<a href="[sitetree_link id=3423423]">this is a broken link</a>';
		$obj->syncLinkTracking();
		$this->assertTrue($obj->HasBrokenLink, 'Page has a broken link');
		
		$obj->Content = '<a href="[sitetree_link id=' . $this->idFromFixture('Page','about') .']">this is not a broken link</a>';
		$obj->syncLinkTracking();
		$this->assertFalse($obj->HasBrokenLink, 'Page does NOT have a broken link');
	}
	
	function testBrokenVirtualPages() {
		$obj = $this->objFromFixture('Page','content');
		$vp = new VirtualPage();
		
		$vp->CopyContentFromID = $obj->ID;		
		$vp->syncLinkTracking();
		$this->assertFalse($vp->HasBrokenLink, 'Working virtual page is NOT marked as broken');
		
		$vp->CopyContentFromID = 12345678;		
		$vp->syncLinkTracking();
		$this->assertTrue($vp->HasBrokenLink, 'Broken virtual page IS marked as such');
	}
	
	function testBrokenInternalRedirectorPages() {
		$obj = $this->objFromFixture('Page','content');
		$rp = new RedirectorPage();
		
		$rp->RedirectionType = 'Internal';
		
		$rp->LinkToID = $obj->ID;		
		$rp->syncLinkTracking();
		$this->assertFalse($rp->HasBrokenLink, 'Working redirector page is NOT marked as broken');
		
		$rp->LinkToID = 12345678;		
		$rp->syncLinkTracking();
		$this->assertTrue($rp->HasBrokenLink, 'Broken redirector page IS marked as such');
	}

	function testBrokenAssetLinks() {
		$obj = $this->objFromFixture('Page','content');
		
		$obj->Content = '<a href="assets/nofilehere.pdf">this is a broken link to a pdf file</a>';
		$obj->syncLinkTracking();
		$this->assertTrue($obj->HasBrokenFile, 'Page has a broken file');
		
		$obj->Content = '<a href="assets/privacypolicy.pdf">this is not a broken file link</a>';
		$obj->syncLinkTracking();
		$this->assertFalse($obj->HasBrokenFile, 'Page does NOT have a broken file');
	}

	function testDeletingFileMarksBackedPagesAsBroken() {
		// Test entry
		$file = new File();
		$file->Filename = 'test-file.pdf';
		$file->write();

		$obj = $this->objFromFixture('Page','content');
		$obj->Content = '<a href="assets/test-file.pdf">link to a pdf file</a>';
		$obj->write();
		$this->assertTrue($obj->doPublish());
		// Confirm that it isn't marked as broken to begin with
		$obj->flushCache();
		$obj = DataObject::get_by_id("SiteTree", $obj->ID);
		$this->assertEquals(0, $obj->HasBrokenFile);

		$liveObj = Versioned::get_one_by_stage("SiteTree", "Live","\"SiteTree\".\"ID\" = $obj->ID");
		$this->assertEquals(0, $liveObj->HasBrokenFile);
		
		// Delete the file
		$file->delete();

		// Confirm that it is marked as broken in both stage and live
		$obj->flushCache();
		$obj = DataObject::get_by_id("SiteTree", $obj->ID);
		$this->assertEquals(1, $obj->HasBrokenFile);

		$liveObj = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $obj->ID");
		$this->assertEquals(1, $liveObj->HasBrokenFile);
	}	
	function testDeletingMarksBackLinkedPagesAsBroken() {
		$this->logInWithPermission('ADMIN');
		
		// Set up two published pages with a link from content -> about
		$linkDest = $this->objFromFixture('Page','about');
		$linkDest->doPublish();
		
		$linkSrc = $this->objFromFixture('Page','content');
		$linkSrc->Content = "<p><a href=\"[sitetree_link id=$linkDest->ID]\">about us</a></p>";
		$linkSrc->write();

		$linkSrc->doPublish();
 		
		// Confirm no broken link
		$this->assertEquals(0, (int)$linkSrc->HasBrokenLink);
		$this->assertEquals(0, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\" 
			WHERE \"ID\" = $linkSrc->ID")->value());
		
		// Delete page from draft
		$linkDestID = $linkDest->ID;
		$linkDest->delete();

		// Confirm draft has broken link, and published doesn't
		$linkSrc->flushCache();
		$linkSrc = $this->objFromFixture('Page', 'content');

		$this->assertEquals(1, (int)$linkSrc->HasBrokenLink);
		$this->assertEquals(0, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\" 
			WHERE \"ID\" = $linkSrc->ID")->value());
			
		// Delete from live
		$linkDest = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $linkDestID");
		$linkDest->doDeleteFromLive();

		// Confirm both draft and published have broken link
		$linkSrc->flushCache();
		$linkSrc = $this->objFromFixture('Page', 'content');

		$this->assertEquals(1, (int)$linkSrc->HasBrokenLink);
		$this->assertEquals(1, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\" 
			WHERE \"ID\" = $linkSrc->ID")->value());
	}

	function testPublishingSourceBeforeDestHasBrokenLink() {
		$this->logInWithPermission('ADMIN');
		
		// Set up two draft pages with a link from content -> about
		$linkDest = $this->objFromFixture('Page','about');
		// Ensure that it's not on the published site
		$linkDest->doDeleteFromLive();
		
		$linkSrc = $this->objFromFixture('Page','content');
		$linkSrc->Content = "<p><a href=\"[sitetree_link id=$linkDest->ID]\">about us</a></p>";
		$linkSrc->write();
		
		// Publish the source of the link, while the dest is still unpublished. 
		$linkSrc->doPublish();
		
		// Verify that the link isn't broken on draft but is broken on published
		$this->assertEquals(0, (int)$linkSrc->HasBrokenLink);
		$this->assertEquals(1, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\" 
			WHERE \"ID\" = $linkSrc->ID")->value());
	}

	
	function testRestoreFixesBrokenLinks() {
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$pageID = $p->ID;
		$this->assertTrue($p->doPublish());

		// Content links are one kind of link to pages
		$p2 = new Page();
		$p2->Title = "regular link";
		$p2->Content = "<a href=\"[sitetree_link id=$p->ID]\">test</a>";
		$p2->write();
		$this->assertTrue($p2->doPublish());

		// Virtual pages are another
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// Redirector links are a third
		$rp = new RedirectorPage();
		$rp->Title = "redirector";
		$rp->LinkType = 'Internal';
		$rp->LinkToID = $p->ID;
		$rp->write();
		$this->assertTrue($rp->doPublish());

		// Confirm that there are no broken links to begin with
		$this->assertFalse($p2->HasBrokenLink);
		$this->assertFalse($vp->HasBrokenLink);
		$this->assertFalse($rp->HasBrokenLink);

		// Unpublish the source page, confirm that the page 2 and RP has a broken link on published
		$p->doUnpublish();
		$p2Live = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $p2->ID);
		$rpLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $rp->ID);
		$this->assertEquals(1, $p2Live->HasBrokenLink);
		$this->assertEquals(1, $rpLive->HasBrokenLink);

		// Delete the source page, confirm that the VP, RP and page 2 have broken links on draft
		$p->delete();
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$p2->flushCache();
		$p2 = DataObject::get_by_id('SiteTree', $p2->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SiteTree', $rp->ID);
		$this->assertEquals(1, $p2->HasBrokenLink);
		$this->assertEquals(1, $vp->HasBrokenLink);
		$this->assertEquals(1, $rp->HasBrokenLink);

		// Restore the page to stage, confirm that this fixes the links
		$p = Versioned::get_latest_version('SiteTree', $pageID);
		$p->doRestoreToStage();

		$p2->flushCache();
		$p2 = DataObject::get_by_id('SiteTree', $p2->ID);
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SiteTree', $rp->ID);
		$this->assertFalse((bool)$p2->HasBrokenLink);
		$this->assertFalse((bool)$vp->HasBrokenLink);
		$this->assertFalse((bool)$rp->HasBrokenLink);

		// Publish and confirm that the p2 and RP broken links are fixed on published
		$this->assertTrue($p->doPublish());
		$p2Live = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $p2->ID);
		$rpLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $rp->ID);
		$this->assertFalse((bool)$p2Live->HasBrokenLink);
		$this->assertFalse((bool)$rpLive->HasBrokenLink);
		
	}

	function testRevertToLiveFixesBrokenLinks() {
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$pageID = $p->ID;
		$this->assertTrue($p->doPublish());

		// Content links are one kind of link to pages
		$p2 = new Page();
		$p2->Title = "regular link";
		$p2->Content = "<a href=\"[sitetree_link id=$p->ID]\">test</a>";
		$p2->write();
		$this->assertTrue($p2->doPublish());

		// Virtual pages are another
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// Redirector links are a third
		$rp = new RedirectorPage();
		$rp->Title = "redirector";
		$rp->LinkType = 'Internal';
		$rp->LinkToID = $p->ID;
		$rp->write();
		$this->assertTrue($rp->doPublish());

		// Confirm that there are no broken links to begin with
		$this->assertFalse($p2->HasBrokenLink);
		$this->assertFalse($vp->HasBrokenLink);
		$this->assertFalse($rp->HasBrokenLink);

		// Delete from draft and confirm that broken links are marked
		$pID = $p->ID;
		$p->delete();
		
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$p2->flushCache();
		$p2 = DataObject::get_by_id('SiteTree', $p2->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SiteTree', $rp->ID);
		$this->assertEquals(1, $p2->HasBrokenLink);
		$this->assertEquals(1, $vp->HasBrokenLink);
		$this->assertEquals(1, $rp->HasBrokenLink);

		// Call doRevertToLive and confirm that broken links are restored
		$pLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $pID);
		$pLive->doRevertToLive();

		$p2->flushCache();
		$p2 = DataObject::get_by_id('SiteTree', $p2->ID);
		$vp->flushCache();
		$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id('SiteTree', $rp->ID);
		$this->assertFalse((bool)$p2->HasBrokenLink);
		$this->assertFalse((bool)$vp->HasBrokenLink);
		$this->assertFalse((bool)$rp->HasBrokenLink);

		// However, the page isn't marked as modified on stage
		$this->assertFalse($p2->IsModifiedOnStage);
		$this->assertFalse($rp->IsModifiedOnStage);

		// This is something that we know to be broken
		//$this->assertFalse($vp->IsModifiedOnStage);

	}
}

?>