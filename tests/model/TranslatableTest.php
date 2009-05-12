<?php
/**
 * @todo Test Versioned getters
 * 
 * @package sapphire
 * @subpackage tests
 */
class TranslatableTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/model/TranslatableTest.yml';
	
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
	
	static function tear_down_once() {
		if(!self::$origTranslatableSettings['has_extension']) Object::remove_extension('SiteTree', 'Translatable');

		Translatable::set_default_locale(self::$origTranslatableSettings['default_locale']);
		Translatable::set_reading_locale(self::$origTranslatableSettings['default_locale']);
		
		self::kill_temp_db();
		self::create_temp_db();
		
		parent::tear_down_once();
	}

	function testTranslationGroups() {
		// first in french
		$frPage = new SiteTree();
		$frPage->Locale = 'fr_FR';
		$frPage->write();
		
		// second in english (from french "original")
		$enPage = $frPage->createTranslation('en_US');
		
		// third in spanish (from the english translation)
		$esPage = $enPage->createTranslation('es_ES');
		
		// test french
		$this->assertEquals(
			$frPage->getTranslations()->column('Locale'),
			array('en_US','es_ES')
		);
		$this->assertNotNull($frPage->getTranslation('en_US'));
		$this->assertEquals(
			$frPage->getTranslation('en_US')->ID,
			$enPage->ID
		);
		$this->assertNotNull($frPage->getTranslation('es_ES'));
		$this->assertEquals(
			$frPage->getTranslation('es_ES')->ID,
			$esPage->ID
		);
		
		// test english
		$this->assertEquals(
			$enPage->getTranslations()->column('Locale'),
			array('fr_FR','es_ES')
		);
		$this->assertNotNull($frPage->getTranslation('fr_FR'));
		$this->assertEquals(
			$enPage->getTranslation('fr_FR')->ID,
			$frPage->ID
		);
		$this->assertNotNull($frPage->getTranslation('es_ES'));
		$this->assertEquals(
			$enPage->getTranslation('es_ES')->ID,
			$esPage->ID
		);
		
		// test spanish
		$this->assertEquals(
			$esPage->getTranslations()->column('Locale'),
			array('fr_FR','en_US')
		);
		$this->assertNotNull($esPage->getTranslation('fr_FR'));
		$this->assertEquals(
			$esPage->getTranslation('fr_FR')->ID,
			$frPage->ID
		);
		$this->assertNotNull($esPage->getTranslation('en_US'));
		$this->assertEquals(
			$esPage->getTranslation('en_US')->ID,
			$enPage->ID
		);
	}

	function testGetTranslationOnSiteTree() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		
		$translatedPage = $origPage->createTranslation('fr_FR');
		$getTranslationPage = $origPage->getTranslation('fr_FR');

		$this->assertNotNull($getTranslationPage);
		$this->assertEquals($getTranslationPage->ID, $translatedPage->ID);
	}
	
	function testGetTranslatedLanguages() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		
		// through createTranslation()
		$translationAf = $origPage->createTranslation('af_ZA');
		
		// create a new language on an unrelated page which shouldnt be returned from $origPage
		$otherPage = new Page();
		$otherPage->write();
		$otherTranslationEs = $otherPage->createTranslation('es_ES');
		
		$this->assertEquals(
			$origPage->getTranslatedLangs(),
			array(
				'af_ZA',
				//'en_US', // default language is not included
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
		$translationDeWithoutOriginal->Locale = 'de_DE';
		$translationDeWithoutOriginal->write();
		$this->assertEquals(
			$translationDeWithoutOriginal->getTranslatedLangs(),
			array(),
			'A translated page without an original doesn\'t return anything through getTranslatedLang()'
		);
	}

	function testTranslationCantHaveSameURLSegmentAcrossLanguages() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de_DE');
		$translatedPage->URLSegment = 'testpage';
		$translatedPage->write();

		$this->assertNotEquals($origPage->URLSegment, $translatedPage->URLSegment);
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
		$pageTranslated = $pageOrigLang->createTranslation('fr_FR');
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
		$translatedPage = $origTestPage->createTranslation('de_DE');
		
		// test in default language
		$resultPagesDefaultLang = DataObject::get(
			'Page',
			sprintf("`SiteTree`.`MenuTitle` = '%s'", 'A Testpage')
		);
		$this->assertEquals($resultPagesDefaultLang->Count(), 2);
		$this->assertContains($origTestPage->ID, $resultPagesDefaultLang->column('ID'));
		$this->assertContains($otherTestPage->ID, $resultPagesDefaultLang->column('ID'));
		$this->assertNotContains($translatedPage->ID, $resultPagesDefaultLang->column('ID'));
		
		// test in custom language
		Translatable::set_reading_locale('de_DE');
		$resultPagesCustomLang = DataObject::get(
			'Page',
			sprintf("`SiteTree`.`MenuTitle` = '%s'", 'A Testpage')
		);
		$this->assertEquals($resultPagesCustomLang->Count(), 1);
		$this->assertNotContains($origTestPage->ID, $resultPagesCustomLang->column('ID'));
		$this->assertNotContains($otherTestPage->ID, $resultPagesCustomLang->column('ID'));
		// casting as a workaround for types not properly set on duplicated dataobjects from createTranslation()
		$this->assertContains((string)$translatedPage->ID, $resultPagesCustomLang->column('ID'));
		
		Translatable::set_reading_locale('en_US');
	}
	
	function testDataObjectGetByIdWithReadingLanguage() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de_DE');
		$compareOrigPage = DataObject::get_by_id('Page', $origPage->ID);
		
		$this->assertEquals(
			$origPage->ID, 
			$compareOrigPage->ID,
			'DataObject::get_by_id() should work independently of the reading language'
		);
	}
	
	function testDataObjectGetOneWithReadingLanguage() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de_DE');
		
		// running the same query twice with different 
		Translatable::set_reading_locale('de_DE');
		$compareTranslatedPage = DataObject::get_one(
			'Page', 
			sprintf("`SiteTree`.`Title` = '%s'", $translatedPage->Title)
		);
		$this->assertNotNull($compareTranslatedPage);
		$this->assertEquals(
			$translatedPage->ID, 
			$compareTranslatedPage->ID,
			"Translated page is found through get_one() when reading lang is not the default language"
		);
		
		// reset language to default
		Translatable::set_reading_locale('en_US');
	}
	
	function testModifyTranslationWithDefaultReadingLang() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de_DE');
		
		Translatable::set_reading_locale('en_US');
		$translatedPage->Title = 'De Modified';
		$translatedPage->write();
		$savedTranslatedPage = $origPage->getTranslation('de_DE');
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
		$translatedPage = $origPage->createTranslation('de_DE');
		
		Translatable::set_reading_locale('en_US');
		$origPage->Title = 'En Modified';
		$origPage->write();
		// modifying a record in language which is not the reading language should still write the record correctly
		$translatedPage->Title = 'De Modified';
		$translatedPage->write();
		$origPage->publish('Stage', 'Live');
		$liveOrigPage = Versioned::get_one_by_stage('Page', 'Live', "`SiteTree`.ID = {$origPage->ID}");
		$this->assertEquals(
			$liveOrigPage->Title, 
			'En Modified',
			'Publishing a record in its original language publshes correct properties'
		);
	}
	
	function testDeletingTranslationKeepsOriginal() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de_DE');
		$translatedPageID = $translatedPage->ID;
		$translatedPage->delete();
		
		$translatedPage->flushCache();
		$origPage->flushCache();

		$this->assertNull($origPage->getTranslation('de_DE'));
		$this->assertNotNull(DataObject::get_by_id('Page', $origPage->ID));
	}
	
	function testHierarchyChildren() {
		$parentPage = $this->objFromFixture('Page', 'parent');
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child2Page = $this->objFromFixture('Page', 'child2');
		$child3Page = $this->objFromFixture('Page', 'child3');
		$grandchildPage = $this->objFromFixture('Page', 'grandchild1');
		
		$parentPageTranslated = $parentPage->createTranslation('de_DE');
		$child4PageTranslated = new SiteTree();
		$child4PageTranslated->Locale = 'de_DE';
		$child4PageTranslated->ParentID = $parentPageTranslated->ID;
		$child4PageTranslated->write();
		
		Translatable::set_reading_locale('en_US');
		$this->assertEquals(
			$parentPage->Children()->column('ID'),
			array(
				$child1Page->ID, 
				$child2Page->ID,
				$child3Page->ID
			),
			"Showing Children() in default language doesnt show children in other languages"
		);
		
		Translatable::set_reading_locale('de_DE');
		$parentPage->flushCache();
		$this->assertEquals(
			$parentPageTranslated->Children()->column('ID'),
			array($child4PageTranslated->ID),
			"Showing Children() in translation mode doesnt show children in default languages"
		);
		
		// reset language
		Translatable::set_reading_locale('en_US');
	}
	
	function testHierarchyLiveStageChildren() {
		$parentPage = $this->objFromFixture('Page', 'parent');
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child1Page->publish('Stage', 'Live');
		$child2Page = $this->objFromFixture('Page', 'child2');
		$child3Page = $this->objFromFixture('Page', 'child3');
		$grandchildPage = $this->objFromFixture('Page', 'grandchild1');
		
		$parentPageTranslated = $parentPage->createTranslation('de_DE');
		
		$child4PageTranslated = new SiteTree();
		$child4PageTranslated->Locale = 'de_DE';
		$child4PageTranslated->ParentID = $parentPageTranslated->ID;
		$child4PageTranslated->write();
		$child4PageTranslated->publish('Stage', 'Live');
		
		$child5PageTranslated = new SiteTree();
		$child5PageTranslated->Locale = 'de_DE';
		$child5PageTranslated->ParentID = $parentPageTranslated->ID;
		$child5PageTranslated->write();
		
		Translatable::set_reading_locale('en_US');
		$this->assertNotNull($parentPage->liveChildren());
		$this->assertEquals(
			$parentPage->liveChildren()->column('ID'),
			array(
				$child1Page->ID
			),
			"Showing liveChildren() in default language doesnt show children in other languages"
		);
		$this->assertNotNull($parentPage->stageChildren());
		$this->assertEquals(
			$parentPage->stageChildren()->column('ID'),
			array(
				$child1Page->ID, 
				$child2Page->ID,
				$child3Page->ID
			),
			"Showing stageChildren() in default language doesnt show children in other languages"
		);
		
		Translatable::set_reading_locale('de_DE');
		$parentPage->flushCache();
		$this->assertNotNull($parentPageTranslated->liveChildren());
		$this->assertEquals(
			$parentPageTranslated->liveChildren()->column('ID'),
			array($child4PageTranslated->ID),
			"Showing liveChildren() in translation mode doesnt show children in default languages"
		);
		$this->assertNotNull($parentPageTranslated->stageChildren());
		$this->assertEquals(
			$parentPageTranslated->stageChildren()->column('ID'),
			array(
				$child4PageTranslated->ID,
				$child5PageTranslated->ID,
			),
			"Showing stageChildren() in translation mode doesnt show children in default languages"
		);
		
		// reset language
		Translatable::set_reading_locale('en_US');
	}
	
	function testTranslatablePropertiesOnSiteTree() {
		$origObj = $this->objFromFixture('TranslatableTest_Page', 'testpage_en');
		
		$translatedObj = $origObj->createTranslation('fr_FR');
		$translatedObj->TranslatableProperty = 'fr_FR';
		$translatedObj->write();
		
		$this->assertEquals(
			$origObj->TranslatableProperty,
			'en_US',
			'Creating a translation doesnt affect database field on original object'
		);
		$this->assertEquals(
			$translatedObj->TranslatableProperty,
			'fr_FR',
			'Translated object saves database field independently of original object'
		);
	}
	
	function testCreateTranslationOnSiteTree() {
		$origPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedPage = $origPage->createTranslation('de_DE');

		$this->assertEquals($translatedPage->Locale, 'de_DE');
		$this->assertNotEquals($translatedPage->ID, $origPage->ID);

		$subsequentTranslatedPage = $origPage->createTranslation('de_DE');
		$this->assertEquals(
			$translatedPage->ID,
			$subsequentTranslatedPage->ID,
			'Subsequent calls to createTranslation() dont cause new records in database'
		);
	}
	
	function testTranslatablePropertiesOnDataObject() {
		$origObj = $this->objFromFixture('TranslatableTest_DataObject', 'testobject_en');
		$translatedObj = $origObj->createTranslation('fr_FR');
		$translatedObj->TranslatableProperty = 'fr_FR';
		$translatedObj->TranslatableDecoratedProperty = 'fr_FR';
		$translatedObj->write();
		
		$this->assertEquals(
			$origObj->TranslatableProperty,
			'en_US',
			'Creating a translation doesnt affect database field on original object'
		);
		$this->assertEquals(
			$origObj->TranslatableDecoratedProperty,
			'en_US',
			'Creating a translation doesnt affect decorated database field on original object'
		);
		$this->assertEquals(
			$translatedObj->TranslatableProperty,
			'fr_FR',
			'Translated object saves database field independently of original object'
		);
		$this->assertEquals(
			$translatedObj->TranslatableDecoratedProperty,
			'fr_FR',
			'Translated object saves decorated database field independently of original object'
		);
	}
	
	function testCreateTranslationWithoutOriginal() {
		$origParentPage = $this->objFromFixture('Page', 'testpage_en');
		$translatedParentPage = $origParentPage->createTranslation('de_DE');

		$translatedPageWithoutOriginal = new SiteTree();
		$translatedPageWithoutOriginal->ParentID = $translatedParentPage->ID;
		$translatedPageWithoutOriginal->Locale = 'de_DE';
		$translatedPageWithoutOriginal->write();

		Translatable::set_reading_locale('de_DE');
		$this->assertEquals(
			$translatedParentPage->stageChildren()->column('ID'),
			array(
				$translatedPageWithoutOriginal->ID
			),
			"Children() still works on a translated page even if no translation group is set"
		);
		
		Translatable::set_reading_locale('en_US');
	}
	
	function testCreateTranslationTranslatesUntranslatedParents() {
		$parentPage = $this->objFromFixture('Page', 'parent');
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child1PageOrigID = $child1Page->ID;
		$grandChild1Page = $this->objFromFixture('Page', 'grandchild1');
		$grandChild2Page = $this->objFromFixture('Page', 'grandchild2');

		$this->assertFalse($grandChild1Page->hasTranslation('de_DE'));
		$this->assertFalse($child1Page->hasTranslation('de_DE'));
		$this->assertFalse($parentPage->hasTranslation('de_DE'));

		$translatedGrandChild1Page = $grandChild1Page->createTranslation('de_DE');
		$translatedGrandChild2Page = $grandChild2Page->createTranslation('de_DE');
		$translatedChildPage = $child1Page->getTranslation('de_DE');
		$translatedParentPage = $parentPage->getTranslation('de_DE');

		$this->assertTrue($grandChild1Page->hasTranslation('de_DE'));
		$this->assertEquals($translatedGrandChild1Page->ParentID, $translatedChildPage->ID);

		$this->assertTrue($grandChild2Page->hasTranslation('de_DE'));
		$this->assertEquals($translatedGrandChild2Page->ParentID, $translatedChildPage->ID);

		$this->assertTrue($child1Page->hasTranslation('de_DE'));
		$this->assertEquals($translatedChildPage->ParentID, $translatedParentPage->ID);

		$this->assertTrue($parentPage->hasTranslation('de_DE'));
	}

	function testHierarchyAllChildrenIncludingDeleted() {
		// Original tree in 'en_US':
		//   parent
		//    child1 (Live only, deleted from stage)
		//    child2 (Stage only, never published)
		//    child3 (Stage only, never published, untranslated)
		// Translated tree in 'de_DE':
		//   parent
		//    child1 (Live only, deleted from stage)
		//    child2 (Stage only)
		
		// Create parent
		$parentPage = $this->objFromFixture('Page', 'parent');
		$parentPageID = $parentPage->ID;
		
		// Create parent translation
		$translatedParentPage = $parentPage->createTranslation('de_DE');
		$translatedParentPageID = $translatedParentPage->ID;
		
		// Create child1
		$child1Page = $this->objFromFixture('Page', 'child1');
		$child1PageID = $child1Page->ID;
		$child1Page->publish('Stage', 'Live');
		
		// Create child1 translation
		$child1PageTranslated = $child1Page->createTranslation('de_DE');
		$child1PageTranslatedID = $child1PageTranslated->ID;
		$child1PageTranslated->publish('Stage', 'Live');
		$child1PageTranslated->deleteFromStage('Stage'); // deleted from stage only, record still exists on live
		$child1Page->deleteFromStage('Stage'); // deleted from stage only, record still exists on live
		
		// Create child2
		$child2Page = $this->objFromFixture('Page', 'child2');
		$child2PageID = $child2Page->ID;
		
		// Create child2 translation
		$child2PageTranslated = $child2Page->createTranslation('de_DE');
		$child2PageTranslatedID = $child2PageTranslated->ID;
		
		// Create child3
		$child3Page = $this->objFromFixture('Page', 'child3');
		$child3PageID = $child3Page->ID;
		
		// on original parent in default language
		Translatable::set_reading_locale('en_US');
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
		Translatable::set_reading_locale('de_DE');
		SiteTree::flush_and_destroy_cache();
		$parentPage = $this->objFromFixture('Page', 'parent');
		$this->assertEquals(
			$translatedParentPage->AllChildrenIncludingDeleted()->column('ID'),
			array(
				$child2PageTranslatedID,
				$child1PageTranslatedID // $child1PageTranslated was deleted from stage, so the original record doesn't have the ID set
			),
			"Showing AllChildrenIncludingDeleted() in translation mode with parent page in translated language shows children in translated language"
		);
		
		Translatable::set_reading_locale('de_DE');
		SiteTree::flush_and_destroy_cache();
		$parentPage = $this->objFromFixture('Page', 'parent');
		$this->assertEquals(
			$parentPage->AllChildrenIncludingDeleted()->column('ID'),
			array(),
			"Showing AllChildrenIncludingDeleted() in translation mode with parent page in translated language shows children in default language"
		);
		
		// reset language
		Translatable::set_reading_locale('en_US');
	}
	
	function testRootUrlDefaultsToTranslatedUrlSegment() {
		$origPage = $this->objFromFixture('Page', 'homepage_en');
		$origPage->publish('Stage', 'Live');
		$translationDe = $origPage->createTranslation('de_DE');
		$translationDe->URLSegment = 'heim';
		$translationDe->write();
		$translationDe->publish('Stage', 'Live');
		
		// test with translatable
		Translatable::set_reading_locale('de_DE');		
		$this->assertEquals(
			RootURLController::get_homepage_urlsegment(), 
			'heim', 
			'Homepage with different URLSegment in non-default language is found'
		);
		
		// @todo Fix add/remove extension
		// test with translatable disabled
		// Object::remove_extension('Page', 'Translatable');
		// 		$_SERVER['HTTP_HOST'] = '/';
		// 		$this->assertEquals(
		// 			RootURLController::get_homepage_urlsegment(), 
		// 			'home', 
		// 			'Homepage is showing in default language if ?lang GET variable is left out'
		// 		);
		// 		Object::add_extension('Page', 'Translatable');
		
		// setting back to default
		Translatable::set_reading_locale('en_US');
	}
	
	function testSiteTreeChangePageTypeInMaster() {
		// create original
		$origPage = new SiteTree();
		$origPage->Locale = 'en_US';
		$origPage->write();
		$origPageID = $origPage->ID;
		
		// create translation
		$translatedPage = $origPage->createTranslation('de_DE');
		$translatedPageID = $translatedPage->ID;
		
		// change page type
		$origPage->ClassName = 'RedirectorPage';
		$origPage->write();
		
		// re-fetch original page with new instance
		$origPageChanged = DataObject::get_by_id('RedirectorPage', $origPageID);
		$this->assertEquals($origPageChanged->ClassName, 'RedirectorPage',
			'A ClassName change to an original page doesnt change original classname'
		);
		
		// re-fetch the translation with new instance
		Translatable::set_reading_locale('de_DE');
		$translatedPageChanged = DataObject::get_by_id('RedirectorPage', $translatedPageID);
		$translatedPageChanged = $origPageChanged->getTranslation('de_DE');
		$this->assertEquals($translatedPageChanged->ClassName, 'RedirectorPage',
			'ClassName change on an original page also changes ClassName attribute of translation'
		);
	}
	
	function testGetTranslationByStage() {
		$publishedPage = new SiteTree();
		$publishedPage->Locale = 'en_US';
		$publishedPage->Title = 'Published';
		$publishedPage->write();
		$publishedPage->publish('Stage', 'Live');
		$publishedPage->Title = 'Unpublished';
		$publishedPage->write();
		
		$publishedTranslatedPage = $publishedPage->createTranslation('de_DE');
		$publishedTranslatedPage->Title = 'Publiziert';
		$publishedTranslatedPage->write();
		$publishedTranslatedPage->publish('Stage', 'Live');
		$publishedTranslatedPage->Title = 'Unpubliziert';
		$publishedTranslatedPage->write();
		
		$compareStage = $publishedPage->getTranslation('de_DE', 'Stage');
		$this->assertNotNull($compareStage);
		$this->assertEquals($compareStage->Title, 'Unpubliziert');
		
		$compareLive = $publishedPage->getTranslation('de_DE', 'Live');
		$this->assertNotNull($compareLive);
		$this->assertEquals($compareLive->Title, 'Publiziert');
	}

}

class TranslatableTest_DataObject extends DataObject implements TestOnly {
	// add_extension() used to add decorator at end of file
	
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