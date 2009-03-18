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
	
	function testTranslationGroups() {
		// first in french
		$frPage = new SiteTree();
		$frPage->Lang = 'fr';
		$frPage->write();
		
		// second in english (from french "original")
		$enPage = $frPage->createTranslation('en');
		
		// third in spanish (from the english translation)
		$esPage = $enPage->createTranslation('es');
		
		// test french
		$this->assertEquals(
			$frPage->getTranslations()->column('Lang'),
			array('en','es')
		);
		$this->assertNotNull($frPage->getTranslation('en'));
		$this->assertEquals(
			$frPage->getTranslation('en')->ID,
			$enPage->ID
		);
		$this->assertNotNull($frPage->getTranslation('es'));
		$this->assertEquals(
			$frPage->getTranslation('es')->ID,
			$esPage->ID
		);
		
		// test english
		$this->assertEquals(
			$enPage->getTranslations()->column('Lang'),
			array('fr','es')
		);
		$this->assertNotNull($frPage->getTranslation('fr'));
		$this->assertEquals(
			$enPage->getTranslation('fr')->ID,
			$frPage->ID
		);
		$this->assertNotNull($frPage->getTranslation('es'));
		$this->assertEquals(
			$enPage->getTranslation('es')->ID,
			$esPage->ID
		);
		
		// test spanish
		$this->assertEquals(
			$esPage->getTranslations()->column('Lang'),
			array('fr','en')
		);
		$this->assertNotNull($esPage->getTranslation('fr'));
		$this->assertEquals(
			$esPage->getTranslation('fr')->ID,
			$frPage->ID
		);
		$this->assertNotNull($esPage->getTranslation('en'));
		$this->assertEquals(
			$esPage->getTranslation('en')->ID,
			$enPage->ID
		);
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
		
		// manual creation of page without an original link
		$translationDeWithoutOriginal = new Page();
		$translationDeWithoutOriginal->Lang = 'de';
		$translationDeWithoutOriginal->write();
		$this->assertEquals(
			$translationDeWithoutOriginal->getTranslatedLangs(),
			array(),
			'A translated page without an original doesn\'t return anything through getTranslatedLang()'
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

		$this->assertNull($origPage->getTranslation('de'));
		$this->assertNotNull(DataObject::get_by_id('Page', $origPage->ID));
	}
	
	function testHierarchyChildren() {
		$parentPage = $this->objFromFixture('Page', 'parent');
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child2Page = $this->objFromFixture('Page', 'child2');
		$child3Page = $this->objFromFixture('Page', 'child3');
		$grandchildPage = $this->objFromFixture('Page', 'grandchild');
		
		$child1PageTranslated = $child1Page->createTranslation('de');
		
		Translatable::set_reading_lang('en');
		$this->assertEquals(
			$parentPage->Children()->column('ID'),
			array(
				$child1Page->ID, 
				$child2Page->ID,
				$child3Page->ID
			),
			"Showing Children() in default language doesnt show children in other languages"
		);
		
		Translatable::set_reading_lang('de');
		$parentPage->flushCache();
		$this->assertEquals(
			$parentPage->Children()->column('ID'),
			array($child1PageTranslated->ID),
			"Showing Children() in translation mode doesnt show children in default languages"
		);
		
		// reset language
		Translatable::set_reading_lang('en');
	}
	
	function testHierarchyLiveStageChildren() {
		$parentPage = $this->objFromFixture('Page', 'parent');
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child1Page->publish('Stage', 'Live');
		$child2Page = $this->objFromFixture('Page', 'child2');
		$child3Page = $this->objFromFixture('Page', 'child3');
		$grandchildPage = $this->objFromFixture('Page', 'grandchild');
		
		$child1PageTranslated = $child1Page->createTranslation('de');
		$child1PageTranslated->publish('Stage', 'Live');
		$child2PageTranslated = $child2Page->createTranslation('de');
		
		Translatable::set_reading_lang('en');
		$this->assertEquals(
			$parentPage->liveChildren()->column('ID'),
			array(
				$child1Page->ID
			),
			"Showing liveChildren() in default language doesnt show children in other languages"
		);
		$this->assertEquals(
			$parentPage->stageChildren()->column('ID'),
			array(
				$child1Page->ID, 
				$child2Page->ID,
				$child3Page->ID
			),
			"Showing stageChildren() in default language doesnt show children in other languages"
		);
		
		Translatable::set_reading_lang('de');
		$parentPage->flushCache();
		$this->assertEquals(
			$parentPage->liveChildren()->column('ID'),
			array($child1PageTranslated->ID),
			"Showing liveChildren() in translation mode doesnt show children in default languages"
		);
		$this->assertEquals(
			$parentPage->stageChildren()->column('ID'),
			array(
				$child2PageTranslated->ID,
				$child1PageTranslated->ID,
			),
			"Showing stageChildren() in translation mode doesnt show children in default languages"
		);
		
		// reset language
		Translatable::set_reading_lang('en');
	}
	
	function testTranslatablePropertiesOnSiteTree() {
		$origObj = $this->objFromFixture('TranslatableTest_Page', 'testpage_en');
		$translatedObj = $origObj->createTranslation('fr');
		$translatedObj->TranslatableProperty = 'Fr';
		$translatedObj->write();
		
		$this->assertEquals(
			$origObj->TranslatableProperty,
			'En',
			'Creating a translation doesnt affect database field on original object'
		);
		$this->assertEquals(
			$translatedObj->TranslatableProperty,
			'Fr',
			'Translated object saves database field independently of original object'
		);
	}
	
	function testCreateTranslationOnSiteTree() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de');

		$this->assertEquals($translatedPage->Lang, 'de');
		$this->assertNotEquals($translatedPage->ID, $origPage->ID);

		$subsequentTranslatedPage = $origPage->createTranslation('de');
		$this->assertEquals(
			$translatedPage->ID,
			$subsequentTranslatedPage->ID,
			'Subsequent calls to createTranslation() dont cause new records in database'
		);
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
	
	function testCreateTranslationWithoutOriginal() {
		$origParentPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedParentPage = $origParentPage->createTranslation('de');

		$translatedPageWithoutOriginal = new SiteTree();
		$translatedPageWithoutOriginal->ParentID = $translatedParentPage->ID;
		$translatedPageWithoutOriginal->Lang = 'de';
		$translatedPageWithoutOriginal->write();

		Translatable::set_reading_lang('de');
		$this->assertEquals(
			$translatedParentPage->stageChildren()->column('ID'),
			array(
				$translatedPageWithoutOriginal->ID
			),
			"Children() still works on a translated page even if no translation group is set"
		);
		
		Translatable::set_reading_lang('en');
	}
	
	function testCreateTranslationTranslatesUntranslatedParents() {
		$parentPage = $this->objFromFixture('Page', 'parent');
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child1PageOrigID = $child1Page->ID;
		$grandchildPage = $this->objFromFixture('Page', 'grandchild');
		
		$this->assertFalse($grandchildPage->hasTranslation('de'));
		$this->assertFalse($child1Page->hasTranslation('de'));
		$this->assertFalse($parentPage->hasTranslation('de'));
		
		$translatedGrandChildPage = $grandchildPage->createTranslation('de');
		$this->assertTrue($grandchildPage->hasTranslation('de'));
		$this->assertTrue($child1Page->hasTranslation('de'));
		$this->assertTrue($parentPage->hasTranslation('de'));
	}

	function testHierarchyAllChildrenIncludingDeleted() {
		// Original tree in 'en':
		//   parent
		//    child1 (Live only, deleted from stage)
		//    child2 (Stage only, never published)
		//    child3 (Stage only, never published, untranslated)
		// Translated tree in 'de':
		//   parent
		//    child1 (Live only, deleted from stage)
		//    child2 (Stage only)
		
		// Create parent
		$parentPage = $this->objFromFixture('Page', 'parent');
		$parentPageID = $parentPage->ID;
		
		// Create parent translation
		$translatedParentPage = $parentPage->createTranslation('de');
		$translatedParentPageID = $translatedParentPage->ID;
		
		// Create child1
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child1PageID = $child1Page->ID;
		$child1Page->publish('Stage', 'Live');
		
		// Create child1 translation
		$child1PageTranslated = $child1Page->createTranslation('de');
		$child1PageTranslatedID = $child1PageTranslated->ID;
		$child1PageTranslated->publish('Stage', 'Live');
		$child1PageTranslated->deleteFromStage('Stage'); // deleted from stage only, record still exists on live
		$child1Page->deleteFromStage('Stage'); // deleted from stage only, record still exists on live
		
		// Create child2
		$child2Page = $this->objFromFixture('Page', 'child2');
		$child2PageID = $child2Page->ID;
		
		// Create child2 translation
		$child2PageTranslated = $child2Page->createTranslation('de');
		$child2PageTranslatedID = $child2PageTranslated->ID;
		
		// Create child3
		$child3Page = $this->objFromFixture('Page', 'child3');
		$child3PageID = $child3Page->ID;
		
		// on original parent in default language
		Translatable::set_reading_lang('en');
		SiteTree::flush_and_destroy_cache();
		$parentPage = $this->objFromFixture('Page', 'parent');
		$children = $parentPage->AllChildrenIncludingDeleted();
		$this->assertEquals(
			$parentPage->AllChildrenIncludingDeleted()->column('ID'),
			array(
				$child2PageID,
				$child3PageID,
				$child1PageID // $child1Page was deleted from stage, so the original record doesn't have the ID set
			),
			"Showing AllChildrenIncludingDeleted() in default language doesnt show deleted children in other languages"
		);

		// on original parent in translation mode
		Translatable::set_reading_lang('de');
		SiteTree::flush_and_destroy_cache();
		$parentPage = $this->objFromFixture('Page', 'parent');
		$this->assertEquals(
			$parentPage->AllChildrenIncludingDeleted()->column('ID'),
			array(
				$child2PageTranslatedID,
				$child1PageTranslatedID // $child1PageTranslated was deleted from stage, so the original record doesn't have the ID set
			),
			"Showing AllChildrenIncludingDeleted() in translation mode with parent page in default language shows children in default language"
		);
		
		// @todo getTranslation() doesn't switch languages for future calls, its handled statically through set_reading_lang()
		// // on translated page in translation mode using getTranslation()
		// 		SiteTree::flush_and_destroy_cache();
		// 		$parentPage = $this->objFromFixture('Page', 'parent');
		// 		$translatedParentPage = $parentPage->getTranslation('de');
		// 		$this->assertEquals(
		// 			$translatedParentPage->AllChildrenIncludingDeleted()->column('ID'),
		// 			array(
		// 				$child2PageTranslatedID,
		// 				$child1PageTranslatedID,
		// 			),
		// 			"Showing AllChildrenIncludingDeleted() in translation mode with translated parent page shows only translated children"
		// 		);
		
		// reset language
		Translatable::set_reading_lang('en');
	}

	function testRootUrlDefaultsToTranslatedUrlSegment() {
		$_originalHost = $_SERVER['HTTP_HOST'];
		
		$origPage = $this->objFromFixture('Page', 'homepage_en');
		$origPage->publish('Stage', 'Live');
		$translationDe = $origPage->createTranslation('de');
		$translationDe->URLSegment = 'heim';
		$translationDe->write();
		$translationDe->publish('Stage', 'Live');
		
		// test with translatable enabled
		$_SERVER['HTTP_HOST'] = '/?lang=de';
		Translatable::set_reading_lang('de');
		$this->assertEquals(
			RootURLController::get_homepage_urlsegment(), 
			'heim', 
			'Homepage with different URLSegment in non-default language is found'
		);
		
		// test with translatable disabled
		Translatable::disable();
		$_SERVER['HTTP_HOST'] = '/';
		$this->assertEquals(
			RootURLController::get_homepage_urlsegment(), 
			'home', 
			'Homepage is showing in default language if ?lang GET variable is left out'
		);
		Translatable::enable();
		
		// setting back to default
		Translatable::set_reading_lang('en');
		$_SERVER['HTTP_HOST'] = $_originalHost;
	}
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

class TranslatableTest_Page extends Page implements TestOnly {
	// static $extensions is inherited from SiteTree,
	// we don't need to explicitly specify the fields
	
	static $db = array(
		'TranslatableProperty' => 'Text'
	);
}

DataObject::add_extension('TranslatableTest_DataObject', 'TranslatableTest_Decorator');
?>