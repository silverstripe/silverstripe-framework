<?php
/**
 * @package sapphire
 * @subpackage testing
 */
class TranslatableSearchFormTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/search/TranslatableSearchFormTest.yml';
	
	protected $mockController;

	protected $requiredExtensions = array(
		'SiteTree' => array(
			'Translatable',
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
		
		// whenever a translation is created, canTranslate() is checked
		$admin = $this->objFromFixture('Member', 'admin');
		$admin->logIn();

		$this->waitUntilIndexingFinished();
	}
	
	
		
	function testPublishedPagesMatchedByTitleInDefaultLanguage() {
		$sf = new SearchForm($this->mockController, 'SearchForm');

		$publishedPage = $this->objFromFixture('SiteTree', 'publishedPage');
		$publishedPage->publish('Stage', 'Live');
		$translatedPublishedPage = $publishedPage->createTranslation('de_DE');
		$translatedPublishedPage->Title = 'translatedPublishedPage';
		$translatedPublishedPage->Content = 'German content';
		$translatedPublishedPage->write();
		$translatedPublishedPage->publish('Stage', 'Live');
		
		$this->waitUntilIndexingFinished();

		// Translatable::set_current_locale() can't be used because the context
		// from the holder is not present here - we set the language explicitly
		// through a pseudo GET variable in getResults()
		
		$lang = 'en_US';
		$results = $sf->getResults(null, array('Search'=>'content', 'locale'=>$lang));
		$this->assertContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages are found by searchform in default language'
		);
		$this->assertNotContains(
			$translatedPublishedPage->ID,
			$results->column('ID'),
			'Published pages in another language are not found when searching in default language'
		);
		
		$lang = 'de_DE';
		$results = $sf->getResults(null, array('Search'=>'content', 'locale'=>$lang));
		$this->assertNotContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages in default language are not found when searching in another language'
		);
		$actual = $results->column('ID');
		array_walk($actual, 'intval');
		$this->assertContains(
			(int)$translatedPublishedPage->ID,
			$actual,
			'Published pages in another language are found when searching in this language'
		);
	}

}
?>
