<?php
/**
 * @package sapphire
 * @subpackage testing
 */
class TranslatableSearchFormTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/search/TranslatableSearchFormTest.yml';
	
	protected $mockController;
	
	/**
	 * @todo Necessary because of monolithic Translatable design
	 */
	static protected $origTranslatableSettings = array();
	
	static function set_up_once() {
		// needs to recreate the database schema with language properties
		self::kill_temp_db();
		
		// store old defaults	
		self::$origTranslatableSettings['has_extension'] = singleton('SiteTree')->hasExtension('Translatable');
		self::$origTranslatableSettings['default_locale'] = Translatable::default_locale();
		
		// overwrite locale
		Translatable::set_default_locale("en_US");

		// refresh the decorated statics - different fields in $db with Translatable enabled
		if(!self::$origTranslatableSettings['has_extension']) Object::add_extension('SiteTree', 'Translatable');
		Object::add_extension('TranslatableTest_DataObject', 'Translatable');
		
		// clear singletons, they're caching old extension info which is used in DatabaseAdmin->doBuild()
		global $_SINGLETONS;
		$_SINGLETONS = array();
		
		// @todo Hack to refresh statics on the newly decorated classes
		$newSiteTree = new SiteTree();
		foreach($newSiteTree->getExtensionInstances() as $extInstance) {
			$extInstance->loadExtraStatics();
		}
		// @todo Hack to refresh statics on the newly decorated classes
		$TranslatableTest_DataObject = new TranslatableTest_DataObject();
		foreach($TranslatableTest_DataObject->getExtensionInstances() as $extInstance) {
			$extInstance->loadExtraStatics();
		}

		// recreate database with new settings
		$dbname = self::create_temp_db();
		DB::set_alternative_database_name($dbname);

		parent::set_up_once();
	}
	
	function setUp() {
		parent::setUp();
		
		$holderPage = $this->objFromFixture('SiteTree', 'searchformholder');
		$this->mockController = new ContentController($holderPage);
	}
	
	static function tear_down_once() {
		if(!self::$origTranslatableSettings['has_extension']) Object::remove_extension('SiteTree', 'Translatable');

		Translatable::set_default_locale(self::$origTranslatableSettings['default_locale']);
		
		self::kill_temp_db();
		self::create_temp_db();
		
		parent::tear_down_once();
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
		
		// Translatable::set_reading_locale() can't be used because the context
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
		$this->assertContains(
			(string)$translatedPublishedPage->ID,
			$results->column('ID'),
			'Published pages in another language are found when searching in this language'
		);
	}

}
?>