<?php

class RedirectorPageTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/RedirectorPageTest.yml';
	static $use_draft_site = true;
	
	function testGoodRedirectors() {
		/* For good redirectors, the final destination URL will be returned */
		$this->assertEquals("http://www.google.com", $this->objFromFixture('RedirectorPage','goodexternal')->Link());
		$this->assertEquals(Director::baseURL() . "redirection-dest/", $this->objFromFixture('RedirectorPage','goodinternal')->redirectionLink());
		$this->assertEquals(Director::baseURL() . "redirection-dest/", $this->objFromFixture('RedirectorPage','goodinternal')->Link());
	}

	function testEmptyRedirectors() {
		/* If a redirector page is misconfigured, then its link method will just return the usual URLSegment-generated value */
		$page1 = $this->objFromFixture('RedirectorPage','badexternal');
		$this->assertEquals(Director::baseURL() . 'bad-external/', $page1->Link());

		/* An error message will be shown if you visit it */
		$content = $this->get(Director::makeRelative($page1->Link()))->getBody();
		$this->assertContains(_t('RedirectorPage.HASBEENSETUP'), $content);

		/* This also applies for internal links */
		$page2 = $this->objFromFixture('RedirectorPage','badinternal');
		$this->assertEquals(Director::baseURL() . 'bad-internal/', $page2->Link());
		$content = $this->get(Director::makeRelative($page2->Link()))->getBody();
		$this->assertContains(_t('RedirectorPage.HASBEENSETUP'), $content);
	}

	function testReflexiveAndTransitiveInternalRedirectors() {
		/* Reflexive redirectors are those that point to themselves.  They should behave the same as an empty redirector */
		$page = $this->objFromFixture('RedirectorPage','reflexive');
		$this->assertEquals(Director::baseURL() . 'reflexive/', $page->Link());
		$content = $this->get(Director::makeRelative($page->Link()))->getBody();
		$this->assertContains(_t('RedirectorPage.HASBEENSETUP'), $content);

		/* Transitive redirectors are those that point to another redirector page.  They should send people to the URLSegment
		 * of the destination page - the middle-stop, so to speak.  That should redirect to the final destination */
		$page = $this->objFromFixture('RedirectorPage','transitive');
		$this->assertEquals(Director::baseURL() . 'good-internal/', $page->Link());
		
		$this->autoFollowRedirection = false;
		$response = $this->get(Director::makeRelative($page->Link()));
		$this->assertEquals(Director::baseURL() . "redirection-dest/", $response->getHeader("Location"));
	}
}

?>