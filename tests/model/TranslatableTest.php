<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TranslatableTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/model/TranslatableTest.yml';
	
	protected $recreateTempDb = true;
	
	/**
	 * @todo Necessary because of monolithic Translatable design
	 */
	protected $origTranslatableSettings = array();
	
	function setUp() {
		$this->origTranslatableSettings['enabled'] = Translatable::is_enabled();
		$this->origTranslatableSettings['default_lang'] = Translatable::default_lang();
		Translatable::enable();
		Translatable::set_default_lang("en");
		
		// needs to recreate the database schema with *_lang tables
		self::kill_temp_db();
		// refresh the decorated statics - different fields in $db with Translatable enabled
		singleton('SiteTree')->loadExtraStatics();
		$dbname = self::create_temp_db();
		DB::set_alternative_database_name($dbname);
		
		parent::setUp();
	}
	
	function tearDown() {
		if(!$this->origTranslatableSettings['enabled']) Translatable::disable();

		Translatable::set_default_lang($this->origTranslatableSettings['default_lang']);
		
		self::kill_temp_db();
		self::create_temp_db();
		
		parent::tearDown();
	}
	
	function testCreateTranslationOnSiteTree() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		
		$this->assertEquals($translatedPage->Lang, 'de');
		$this->assertNotEquals($translatedPage->ID, $origPage->ID);
		$this->assertEquals($translatedPage->OriginalID, $origPage->ID);
		
		$subsequentTranslatedPage = $origPage->createTranslation('de');
		$this->assertEquals(
			$translatedPage->ID,
			$subsequentTranslatedPage->ID,
			'Subsequent calls to createTranslation() dont cause new records in database'
		);
	}
	
	function testGetOriginalPage() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		
		$this->assertEquals($translatedPage->getOriginalPage()->ID, $origPage->ID);
	}
	
	function testIsTranslation() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		
		$this->assertFalse($origPage->isTranslation());
		$this->assertTrue($translatedPage->isTranslation());
	}
	
	function testGetTranslationOnSiteTree() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $this->objFromFixture('Page', 'testpage_fr');
		
		$getTranslationPage = $origPage->getTranslation('fr');
		$this->assertNotNull($getTranslationPage);
		$this->assertEquals($getTranslationPage->ID, $translatedPage->ID);
	}
	
	function testGetTranslatedLanguages() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		// manual creation of page
		$translationDe = new Page();
		$translationDe->OriginalID = $origPage->ID;
		$translationDe->Lang = 'de';
		$translationDe->write();
		
		// through createTranslation()
		$translationAf = $origPage->createTranslation('af');
		
		// create a new language on an unrelated page which shouldnt be returned from $origPage
		$otherPage = new Page();
		$otherPage->write();
		$otherTranslationEs = $otherPage->createTranslation('es');
		
		$this->assertEquals(
			$origPage->getTranslatedLangs(),
			array(
				'af',
				'de', 
				//'en', // default language is not included
				'fr', // already in the fixtures
			),
			'Language codes are returned specifically for the queried page through getTranslatedLangs()'
		);
		
		$pageWithoutTranslations = new Page();
		$pageWithoutTranslations->write();
		$this->assertEquals(
			$pageWithoutTranslations->getTranslatedLangs(),
			array(),
			'A page without translations returns empty array through getTranslatedLangs(), ' . 
			'even if translations for other pages exist in the database'
		);
	}
	
	function testTranslationCanHaveSameURLSegment() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		$translatedPage->URLSegment = 'testpage';
		$translatedPage->publish('Stage', 'Live');
		
		$this->assertEquals($origPage->URLSegment, $translatedPage->URLSegment);
	}
	
	function testUpdateCMSFieldsOnSiteTree() {
		$pageOrigLang = $this->objFromFixture('Page', 'testpage_en');
		
		// first test with default language
		$fields = $pageOrigLang->getCMSFields();
		$this->assertType(
			'TextField', 
			$fields->dataFieldByName('Title'),
			'Translatable doesnt modify fields if called in default language (e.g. "non-translation mode")'
		);
		$this->assertNull( 
			$fields->dataFieldByName('Title_original'),
			'Translatable doesnt modify fields if called in default language (e.g. "non-translation mode")'
		);
		
		// then in "translation mode"
		$pageTranslated = $this->objFromFixture('Page', 'testpage_fr');
		$fields = $pageTranslated->getCMSFields();
		$this->assertType(
			'TextField', 
			$fields->dataFieldByName('Title'),
			'Translatable leaves original formfield intact in "translation mode"'
		);
		$readonlyField = $fields->dataFieldByName('Title')->performReadonlyTransformation();
		$this->assertType(
			$readonlyField->class, 
			$fields->dataFieldByName('Title_original'),
			'Translatable adds the original value as a ReadonlyField in "translation mode"'
		);
		
	}
}
?>