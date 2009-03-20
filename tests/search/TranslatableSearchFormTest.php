<?php
/**
 * @package sapphire
 * @subpackage testing
 */
class TranslatableSearchFormTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/search/TranslatableSearchFormTest.yml';
	
	protected $mockController;
	
	protected $recreateTempDb = true;
	
	function setUp() {
		$this->origTranslatableSettings['enabled'] = Translatable::is_enabled();
		$this->origTranslatableSettings['default_locale'] = Translatable::default_locale();
		Translatable::enable();
		Translatable::set_default_locale("en");
		
		// needs to recreate the database schema with language properties
		self::kill_temp_db();
		// refresh the decorated statics - different fields in $db with Translatable enabled
		singleton('SiteTree')->loadExtraStatics();
		singleton('TranslatableTest_DataObject')->loadExtraStatics();
		$dbname = self::create_temp_db();
		DB::set_alternative_database_name($dbname);
		
		parent::setUp();
		
		$holderPage = $this->objFromFixture('SiteTree', 'searchformholder');
		$this->mockController = new ContentController($holderPage);
	}
	
	function tearDown() {
		if(!$this->origTranslatableSettings['enabled']) Translatable::disable();

		Translatable::set_default_locale($this->origTranslatableSettings['default_locale']);
		
		self::kill_temp_db();
		self::create_temp_db();
		
		parent::tearDown();
	}
		
	function testPublishedPagesMatchedByTitleInDefaultLanguage() {
		$sf = new SearchForm($this->mockController, 'SearchForm');

		$publishedPage = $this->objFromFixture('SiteTree', 'publishedPage');
		$publishedPage->publish('Stage', 'Live');
		$translatedPublishedPage = $publishedPage->createTranslation('de');
		$translatedPublishedPage->Title = 'translatedPublishedPage';
		$translatedPublishedPage->Content = 'German content';
		$translatedPublishedPage->write();
		$translatedPublishedPage->publish('Stage', 'Live');
		
		// Translatable::set_reading_locale() can't be used because the context
		// from the holder is not present here - we set the language explicitly
		// through a pseudo GET variable in getResults()
		
		$lang = 'en';
		$results = $sf->getResults(null, array('Search'=>'content', 'lang'=>$lang));
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
		
		$lang = 'de';
		$results = $sf->getResults(null, array('Search'=>'content', 'lang'=>$lang));
		$this->assertNotContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages in default language are not found when searching in another language'
		);
		$this->assertContains(
			(string)$translatedPublishedPage->ID,
			$results->column('ID'),
			'Published pages in another language are found when searching in this language'
		);
	}

}
?>