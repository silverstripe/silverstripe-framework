<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SiteTreeBrokenLinksTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/SiteTreeBrokenLinksTest.yml';

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
	
}

?>