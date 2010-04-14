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
		
		$obj->Content = '<a href="no-page-here/">this is a broken link</a>';
		$obj->syncLinkTracking();
		$this->assertTrue($obj->HasBrokenLink, 'Page has a broken link');
		
		$obj->Content = '<a href="about/">this is not a broken link</a>';
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
	
	function testDeletingMarksBackLinkedPagesAsBroken() {
		$this->logInWithPermission('ADMIN');
		
		// Set up two published pages with a link from content -> about
		$linkDest = $this->objFromFixture('Page','about');
		$linkDest->doPublish();
		
		$linkSrc = $this->objFromFixture('Page','content');
		$linkSrc->Content = "<p><a href=\"" . $linkDest->URLSegment . "/\">about us</a></p>";
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
		$linkSrc->Content = "<p><a href=\"" . $linkDest->URLSegment . "/\">about us</a></p>";
		$linkSrc->write();
		
		// Publish the source of the link, while the dest is still unpublished. 
		$linkSrc->doPublish();
		
		// Verify that the link isn't broken on draft but is broken on published
		$this->assertEquals(0, (int)$linkSrc->HasBrokenLink);
		$this->assertEquals(1, DB::query("SELECT \"HasBrokenLink\" FROM \"SiteTree_Live\" 
			WHERE \"ID\" = $linkSrc->ID")->value());
	}
	
}

?>