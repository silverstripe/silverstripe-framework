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
	
	protected $requiredExtensions = array(
		"SiteTree" => array(
			"FulltextSearchable('Title,MenuTitle,Content,MetaTitle,MetaDescription,MetaKeywords')",
		),
		"File" => array(
			"FulltextSearchable('Filename,Title,Content')",
		),
		"ContentController" => array(
			"ContentControllerSearchExtension",
		),
	);
	
	function waitUntilIndexingFinished() {
		$db = DB::getConn();
		if (method_exists($db, 'waitUntilIndexingFinished')) DB::getConn()->waitUntilIndexingFinished();
	}
	
	function setUpOnce() {
		// HACK Postgres doesn't refresh TSearch indexes when the schema changes after CREATE TABLE
		if(is_a(DB::getConn(), 'PostgreSQLDatabase')) {
			self::kill_temp_db();
		}
		
		parent::setUpOnce();
	}
	
	function setUp() {
		parent::setUp();
		
		$holderPage = $this->objFromFixture('SiteTree', 'searchformholder');
		$this->mockController = new ContentController($holderPage);
		
		$this->waitUntilIndexingFinished();
	}
	
	function testPublishedPagesMatchedByTitle() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
	
		$publishedPage = $this->objFromFixture('SiteTree', 'publicPublishedPage');
		$publishedPage->publish('Stage', 'Live');
		
		$this->waitUntilIndexingFinished();
		$results = $sf->getResults(null, array('Search'=>'publicPublishedPage'));
		$this->assertContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages are found by searchform'
		);
	}
	
	function testDoubleQuotesPublishedPagesMatchedByTitle() {
		$sf = new SearchForm($this->mockController, 'SearchForm');

		$publishedPage = $this->objFromFixture('SiteTree', 'publicPublishedPage');
		$publishedPage->Title = "finding butterflies";
		$publishedPage->write();
		$publishedPage->publish('Stage', 'Live');
		
		$this->waitUntilIndexingFinished();
		$results = $sf->getResults(null, array('Search'=>'"finding butterflies"'));
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

	function testSearchTitleAndContentWithSpecialCharacters() {
		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$pageWithSpecialChars = $this->objFromFixture('SiteTree', 'pageWithSpecialChars');
		$pageWithSpecialChars->publish('Stage', 'Live');
		
		$results = $sf->getResults(null, array('Search'=>'Brötchen'));
		$this->assertContains(
			$pageWithSpecialChars->ID,
			$results->column('ID'),
			'Published pages with umlauts in title are found'
		);
		
		$results = $sf->getResults(null, array('Search'=>'Bäcker'));
		$this->assertContains(
			$pageWithSpecialChars->ID,
			$results->column('ID'),
			'Published pages with htmlencoded umlauts in content are found'
		);
	}
}
?>