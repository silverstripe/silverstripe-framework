<?php
/**
 * @package sapphire
 * @subpackage testing
 * 
 * @todo Fix unpublished pages check in testPublishedPagesMatchedByTitle()
 * @todo All tests run on unpublished pages at the moment, due to the searchform not distinguishing between them
 */
class SearchFormTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/search/SearchFormTest.yml';
	
	protected $mockController;
	
	function setUp() {
		parent::setUp();
		
		$holderPage = $this->objFromFixture('SiteTree', 'searchformholder');
		$this->mockController = new ContentController($holderPage);
	}
	
	function testPublishedPagesMatchedByTitle() {
		$sf = new SearchForm($this->mockController, 'SearchForm');

		$publishedPage = $this->objFromFixture('SiteTree', 'publicPublishedPage');
		$publishedPage->publish('Stage', 'Live');
		$results = $sf->getResults(null, array('Search'=>'publicPublishedPage'));
		$this->assertContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages are found by searchform'
		);
	}
	
	/*
	function testUnpublishedPagesNotIncluded() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$results = $sf->getResults(null, array('Search'=>'publicUnpublishedPage'));
		$unpublishedPage = $this->objFromFixture('SiteTree', 'publicUnpublishedPage');
		$this->assertNotContains(
			$unpublishedPage->ID,
			$results->column('ID'),
			'Unpublished pages are not found by searchform'
		);
	}
	*/
	
	function testPagesRestrictedToLoggedinUsersNotIncluded() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'restrictedViewLoggedInUsers');
		$results = $sf->getResults(null, array('Search'=>'restrictedViewLoggedInUsers'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to logged in users" doesnt show without valid login'
		);
		
		$member = $this->objFromFixture('Member', 'randomuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'restrictedViewLoggedInUsers'));
		$this->assertContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to logged in users" shows if login is present'
		);
		$member->logOut();
	}

	function testPagesRestrictedToSpecificGroupNotIncluded() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'restrictedViewOnlyWebsiteUsers');
		$results = $sf->getResults(null, array('Search'=>'restrictedViewOnlyWebsiteUsers'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to these users" doesnt show without valid login'
		);
		
		$member = $this->objFromFixture('Member', 'randomuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'restrictedViewOnlyWebsiteUsers'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to these users" doesnt show if logged in user is not in the right group'
		);
		$member->logOut();
		
		$member = $this->objFromFixture('Member', 'websiteuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'restrictedViewOnlyWebsiteUsers'));
		$this->assertContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to these users" shows if user in this group is logged in'
		);
		$member->logOut();
	}
	
	function testInheritedRestrictedPagesNotInlucded() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'inheritRestrictedView');
		
		$results = $sf->getResults(null, array('Search'=>'inheritRestrictedView'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page inheriting "Restrict to loggedin users" doesnt show without valid login'
		);
		
		$member = $this->objFromFixture('Member', 'websiteuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'inheritRestrictedView'));
		$this->assertContains(
			$page->ID,
			$results->column('ID'),
			'Page inheriting "Restrict to loggedin users" shows if user in this group is logged in'
		);
		$member->logOut();
	}
	
	function testDisabledShowInSearchFlagNotIncluded() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'dontShowInSearchPage');
		$results = $sf->getResults(null, array('Search'=>'dontShowInSearchPage'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Show in Search" disabled doesnt show'
		);
	}
}
?>