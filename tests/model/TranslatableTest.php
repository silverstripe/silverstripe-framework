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
		
		// needs to recreate the database schema with language properties
		self::kill_temp_db();
		// refresh the decorated statics - different fields in $db with Translatable enabled
		singleton('SiteTree')->loadExtraStatics();
		singleton('TranslatableTest_DataObject')->loadExtraStatics();
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
	
	function testTranslatablePropertiesOnDataObject() {
		$origObj = $this->objFromFixture('TranslatableTest_DataObject', 'testobject_en');
		$translatedObj = $origObj->createTranslation('fr');
		$translatedObj->TranslatableProperty = 'Fr';
		$translatedObj->TranslatableDecoratedProperty = 'Fr';
		$translatedObj->write();
		
		$this->assertEquals(
			$origObj->TranslatableProperty,
			'En',
			'Creating a translation doesnt affect database field on original object'
		);
		$this->assertEquals(
			$origObj->TranslatableDecoratedProperty,
			'En',
			'Creating a translation doesnt affect decorated database field on original object'
		);
		$this->assertEquals(
			$translatedObj->TranslatableProperty,
			'Fr',
			'Translated object saves database field independently of original object'
		);
		$this->assertEquals(
			$translatedObj->TranslatableDecoratedProperty,
			'Fr',
			'Translated object saves decorated database field independently of original object'
		);
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
		$translatedPage = $origPage->createTranslation('fr');
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
		$pageTranslated = $pageOrigLang->createTranslation('fr');
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
	
	function testDataObjectGetWithReadingLanguage() {
		$origTestPage = $this->objFromFixture('Page', 'testpage_en');
		$otherTestPage = $this->objFromFixture('Page', 'othertestpage_en');
		$translatedPage = $origTestPage->createTranslation('de');
		
		// test in default language
		$resultPagesDefaultLang = DataObject::get(
			'Page',
			sprintf("\"SiteTree\".\"MenuTitle\" = '%s'", 'A Testpage')
		);
		$this->assertEquals($resultPagesDefaultLang->Count(), 2);
		$this->assertContains($origTestPage->ID, $resultPagesDefaultLang->column('ID'));
		$this->assertContains($otherTestPage->ID, $resultPagesDefaultLang->column('ID'));
		$this->assertNotContains($translatedPage->ID, $resultPagesDefaultLang->column('ID'));
		
		// test in custom language
		Translatable::set_reading_lang('de');
		$resultPagesCustomLang = DataObject::get(
			'Page',
			sprintf("\"SiteTree\".\"MenuTitle\" = '%s'", 'A Testpage')
		);
		$this->assertEquals($resultPagesCustomLang->Count(), 1);
		$this->assertNotContains($origTestPage->ID, $resultPagesCustomLang->column('ID'));
		$this->assertNotContains($otherTestPage->ID, $resultPagesCustomLang->column('ID'));
		// casting as a workaround for types not properly set on duplicated dataobjects from createTranslation()
		$this->assertContains((string)$translatedPage->ID, $resultPagesCustomLang->column('ID'));
		
		Translatable::set_reading_lang('en');
	}
	
	function testDataObjectGetByIdWithReadingLanguage() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		$compareOrigPage = DataObject::get_by_id('Page', $origPage->ID);
		
		$this->assertEquals(
			$origPage->ID, 
			$compareOrigPage->ID,
			'DataObject::get_by_id() should work independently of the reading language'
		);
	}
	
	function testDataObjectGetOneWithReadingLanguage() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		
		// running the same query twice with different 
		Translatable::set_reading_lang('de');
		$compareTranslatedPage = DataObject::get_one(
			'Page', 
			sprintf("\"SiteTree\".\"Title\" = '%s'", $translatedPage->Title)
		);
		$this->assertNotNull($compareTranslatedPage);
		$this->assertEquals(
			$translatedPage->ID, 
			$compareTranslatedPage->ID,
			"Translated page is found through get_one() when reading lang is not the default language"
		);
		
		// reset language to default
		Translatable::set_reading_lang('de');
	}
	
	function testModifyTranslationWithDefaultReadingLang() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		
		Translatable::set_reading_lang('en');
		$translatedPage->Title = 'De Modified';
		$translatedPage->write();
		$savedTranslatedPage = $origPage->getTranslation('de');
		$this->assertEquals(
			$savedTranslatedPage->Title, 
			'De Modified',
			'Modifying a record in language which is not the reading language should still write the record correctly'
		);
		$this->assertEquals(
			$origPage->Title, 
			'Home',
			'Modifying a record in language which is not the reading language does not modify the original record'
		);
	}
	
	function testSiteTreePublication() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		
		Translatable::set_reading_lang('en');
		$origPage->Title = 'En Modified';
		$origPage->write();
		// modifying a record in language which is not the reading language should still write the record correctly
		$translatedPage->Title = 'De Modified';
		$translatedPage->write();
		$origPage->publish('Stage', 'Live');
		$liveOrigPage = Versioned::get_one_by_stage('Page', 'Live', "\"SiteTree\".ID = {$origPage->ID}");
		$this->assertEquals(
			$liveOrigPage->Title, 
			'En Modified',
			'Publishing a record in its original language publshes correct properties'
		);
	}
	
	function testDeletingTranslationKeepsOriginal() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');
		$translatedPageID = $translatedPage->ID;
		$translatedPage->delete();
		
		$translatedPage->flushCache();
		$origPage->flushCache();

		$this->assertFalse($origPage->getTranslation('de'));
		$this->assertNotNull(DataObject::get_by_id('Page', $origPage->ID));
	}
	
	/*
	function testSiteTreeHierarchyTranslation() {
		//$parentPage = $this->objFromFixture();
	}
	*/
}

class TranslatableTest_DataObject extends DataObject implements TestOnly {
	static $extensions = array(
		"Translatable",
	);
	
	static $db = array(
		'TranslatableProperty' => 'Text'
	);
}

class TranslatableTest_Decorator extends DataObjectDecorator implements TestOnly {
	
	function extraStatics() {
		return array(
			'db' => array(
				'TranslatableDecoratedProperty' => 'Text'
			)
		);
	}
}

DataObject::add_extension('TranslatableTest_DataObject', 'TranslatableTest_Decorator');
?>