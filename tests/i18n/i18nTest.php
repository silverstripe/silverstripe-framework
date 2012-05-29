<?php
require_once 'Zend/Translate.php';

/**
 * @package framework
 * @subpackage tests
 */
class i18nTest extends SapphireTest {
	
	/**
	 * @var string $tmpBasePath Used to write language files.
	 * We don't want to store them inside framework (or in any web-accessible place)
	 * in case something goes wrong with the file parsing.
	 */
	protected $alternateBaseSavePath;
	
	/**
	 * @var string $alternateBasePath Fake webroot with a single module
	 * /i18ntestmodule which contains some files with _t() calls.
	 */
	protected $alternateBasePath;

	protected $extraDataObjects = array(
		'i18nTest_DataObject'
	);

	
	function setUp() {
		parent::setUp();
		
		$this->alternateBasePath = $this->getCurrentAbsolutePath() . "/_fakewebroot";
		$this->alternateBaseSavePath = TEMP_FOLDER . '/i18nTextCollectorTest_webroot';
		FileSystem::makeFolder($this->alternateBaseSavePath);
		Director::setBaseFolder($this->alternateBasePath);

		// Push a template loader running from the fake webroot onto the stack.
		$templateManifest = new SS_TemplateManifest($this->alternateBasePath, false, true);
		$templateManifest->regenerate(false);
		SS_TemplateLoader::instance()->pushManifest($templateManifest);
		$this->_oldTheme = SSViewer::current_theme();
		SSViewer::set_theme('testtheme1');

		$this->originalLocale = i18n::get_locale();
		
		// Override default adapter to avoid cached translations between tests.
		// Emulates behaviour in i18n::get_translators()
		$this->origAdapter = i18n::get_translator('core');
		$adapter = new Zend_Translate(array(
			'adapter' => 'i18nRailsYamlAdapter',
			'locale' => i18n::default_locale(),
			'disableNotices' => true,
		));
		i18n::register_translator($adapter, 'core');
		$adapter->removeCache();
		i18n::include_by_locale('en');
	}
	
	function tearDown() {
		SS_TemplateLoader::instance()->popManifest();
		i18n::set_locale($this->originalLocale);
		Director::setBaseFolder(null);
		SSViewer::set_theme($this->_oldTheme);
		i18n::register_translator($this->origAdapter, 'core');
		
		parent::tearDown();
	}
	
	function testDateFormatFromLocale() {
		i18n::set_locale('en_US');
		$this->assertEquals('MMM d, y', i18n::get_date_format());
		i18n::set_locale('en_NZ');
		$this->assertEquals('d/MM/yyyy', i18n::get_date_format());
		i18n::set_locale('en_US');
	}
	
	function testTimeFormatFromLocale() {
		i18n::set_locale('en_US');
		$this->assertEquals('h:mm:ss a', i18n::get_time_format());
		i18n::set_locale('de_DE');
		$this->assertEquals('HH:mm:ss', i18n::get_time_format());
		i18n::set_locale('en_US');
	}
	
	function testDateFormatCustom() {
		i18n::set_locale('en_US');
		$this->assertEquals('MMM d, y', i18n::get_date_format());
		i18n::set_date_format('d/MM/yyyy');
		$this->assertEquals('d/MM/yyyy', i18n::get_date_format());
	}
	
	function testTimeFormatCustom() {
		i18n::set_locale('en_US');
		$this->assertEquals('h:mm:ss a', i18n::get_time_format());
		i18n::set_time_format('HH:mm:ss');
		$this->assertEquals('HH:mm:ss', i18n::get_time_format());
	}
	
	function testGetExistingTranslations() {
		$translations = i18n::get_existing_translations();
		$this->assertTrue(isset($translations['en_US']), 'Checking for en_US translation');
		$this->assertEquals($translations['en_US'], 'English (United States)');
		$this->assertTrue(isset($translations['de_DE']), 'Checking for de_DE translation');
	}
	
	function testDataObjectFieldLabels() {
		$oldLocale = i18n::get_locale();
		i18n::set_locale('de_DE');
		$obj = new i18nTest_DataObject();
		
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTest_DataObject.MyProperty' => 'MyProperty'
		), 'en_US');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTest_DataObject.MyProperty' => 'Mein Attribut'
		), 'de_DE');

		$this->assertEquals(
			$obj->fieldLabel('MyProperty'),
			'Mein Attribut'
		);
		
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTest_DataObject.MyUntranslatedProperty' => 'Mein Attribut'
		), 'en_US');
		$this->assertEquals(
			$obj->fieldLabel('MyUntranslatedProperty'),
			'My Untranslated Property'
		);
		
		i18n::set_locale($oldLocale);
	}
	
	function testProvideI18nEntities() {
		$oldLocale = i18n::get_locale();
		i18n::set_locale('en_US');
		
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTest_Object.MyProperty' => 'Untranslated'
		), 'en_US');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTest_Object.my_translatable_property' => 'Übersetzt'
		), 'de_DE');
		
		$this->assertEquals(
			i18nTest_Object::$my_translatable_property,
			'Untranslated'
		);
		$this->assertEquals(
			i18nTest_Object::my_translatable_property(),
			'Untranslated'
		);
		
		i18n::set_locale('en_US');
		$this->assertEquals(
			i18nTest_Object::my_translatable_property(),
			'Untranslated',
			'Getter returns original static value when called in default locale'
		);
		
		i18n::set_locale('de_DE');
		$this->assertEquals(
			i18nTest_Object::my_translatable_property(),
			'Übersetzt',
			'Getter returns translated value when called in another locale'
		);
	}
	
	function testTemplateTranslation() {
		$oldLocale = i18n::get_locale();

		i18n::set_locale('en_US');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.MAINTEMPLATE' => 'Main Template',
			'i18nTestModule.ss.SPRINTFNONAMESPACE' => 'My replacement no namespace: %s',
			'i18nTestModule.LAYOUTTEMPLATE' => 'Layout Template',
			'i18nTestModule.ss.LAYOUTTEMPLATENONAMESPACE' => 'Layout Template no namespace',
			'i18nTestModule.SPRINTFNAMESPACE' => 'My replacement: %s',
			'i18nTestModule.WITHNAMESPACE' => 'Include Entity with Namespace',
			'i18nTestModuleInclude.ss.NONAMESPACE' => 'Include Entity without Namespace',
			'i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE' => 'My include replacement: %s',
			'i18nTestModuleInclude.ss.SPRINTFINCLUDENONAMESPACE' => 'My include replacement no namespace: %s'
		), 'en_US');
		
		$viewer = new SSViewer('i18nTestModule');
		$parsedHtml = $viewer->process(new ArrayData(array('TestProperty' => 'TestPropertyValue')));
		$this->assertContains(
			"Layout Template\n",
			$parsedHtml
		);
		$this->assertContains(
			"Layout Template no namespace\n",
			$parsedHtml
		);
		
		i18n::set_locale('de_DE');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.MAINTEMPLATE' => 'TRANS Main Template',
			'i18nTestModule.ss.SPRINTFNONAMESPACE' => 'TRANS My replacement no namespace: %s',
			'i18nTestModule.LAYOUTTEMPLATE' => 'TRANS Layout Template',
			'i18nTestModule.ss.LAYOUTTEMPLATENONAMESPACE' => 'TRANS Layout Template no namespace',
			'i18nTestModule.SPRINTFNAMESPACE' => 'TRANS My replacement: %s',
			'i18nTestModule.WITHNAMESPACE' => 'TRANS Include Entity with Namespace',
			'i18nTestModuleInclude.ss.NONAMESPACE' => 'TRANS Include Entity without Namespace',
			'i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE' => 'TRANS My include replacement: %s',
			'i18nTestModuleInclude.ss.SPRINTFINCLUDENONAMESPACE' => 'TRANS My include replacement no namespace: %s'
		), 'de_DE');

		$viewer = new SSViewer('i18nTestModule');
		$parsedHtml = $viewer->process(new ArrayData(array('TestProperty' => 'TestPropertyValue')));
		$this->assertContains(
			"TRANS Main Template\n",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS Layout Template\n",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS Layout Template no namespace",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS My replacement: TestPropertyValue",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS Include Entity with Namespace",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS Include Entity without Namespace",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS My include replacement: TestPropertyValue",
			$parsedHtml
		);
		$this->assertContains(
			"TRANS My include replacement no namespace: TestPropertyValue",
			$parsedHtml
		);
		
		i18n::set_locale($oldLocale);
	}

	function testNewTMethodSignature() {
		global $lang;
		$oldLocale = i18n::get_locale();

		i18n::set_locale('en_US');

		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.NEWMETHODSIG' => 'TRANS New _t method signature test',
			'i18nTestModule.INJECTIONS' => 'TRANS Hello {name} {greeting}. But it is late, {goodbye}',
			'i18nTestModule.INJECTIONSLEGACY' => 'TRANS Hello %s %s. But it is late, %s',
		), 'en_US');

		$entity = "i18nTestModule.INJECTIONS";
		$default = "Hello {name} {greeting}. But it is late, {goodbye}";

		$translated = i18n::_t('i18nTestModule.NEWMETHODSIG',"New _t method signature test");
		$this->assertContains(
			"TRANS New _t method signature test",
			$translated
		);

		$translated = i18n::_t($entity.'_DOES_NOT_EXIST', $default, array("name"=>"Mark", "greeting"=>"welcome", "goodbye"=>"bye"));
		$this->assertContains(
			"Hello Mark welcome. But it is late, bye",
			$translated, "Testing fallback to the translation default (but using the injection array)"
		);

		$translated = i18n::_t($entity, $default, array("name"=>"Paul", "greeting"=>"good you are here", "goodbye"=>"see you"));
		$this->assertContains(
			"TRANS Hello Paul good you are here. But it is late, see you",
			$translated, "Testing entity, default string and injection array"
		);

		$translated = i18n::_t($entity, $default, "New context (this should be ignored)", array("name"=>"Steffen", "greeting"=>"willkommen", "goodbye"=>"wiedersehen"));
		$this->assertContains(
			"TRANS Hello Steffen willkommen. But it is late, wiedersehen",
			$translated, "Full test of translation, using default, context and injection array"
		);

		$translated = i18n::_t($entity, array("name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"));
		$this->assertContains(
			"TRANS Hello Cat meow. But it is late, meow",
			$translated, "Testing a translation with just entity and injection array"
		);

		$translated = i18n::_t(
			'i18nTestModule.INJECTIONSLEGACY', // has %s placeholders
			array("name"=>"Cat", "greeting2"=>"meow", "goodbye"=>"meow")
		);
		$this->assertContains(
			"TRANS Hello Cat meow. But it is late, meow",
			$translated, "Testing sprintf placeholders with named injections"
		);

		$translated = i18n::_t(
			'i18nTestModule.INJECTIONS', // has {name} placeholders
			array("Cat", "meow", "meow")
		);
		$this->assertContains(
			"TRANS Hello Cat meow. But it is late, meow",
			$translated, "Testing named injection placeholders with unnamed injections"
		);

		i18n::set_locale($oldLocale);
	}

	/**
	 * See @i18nTestModule.ss for the template that is being used for this test
	 * */
	function testNewTemplateTranslation() {
		global $lang;
		$oldLocale = i18n::get_locale();

		i18n::set_locale('en_US');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.NEWMETHODSIG' => 'TRANS New _t method signature test',
			'i18nTestModule.INJECTIONS' => 'TRANS Hello {name} {greeting}. But it is late, {goodbye}'
		),'en_US');

		$viewer = new SSViewer('i18nTestModule');
		$parsedHtml = $viewer->process(new ArrayData(array('TestProperty' => 'TestPropertyValue')));
		$this->assertContains(
			"Hello Mark welcome. But it is late, bye\n",
			$parsedHtml, "Testing fallback to the translation default (but using the injection array)"
		);

		$this->assertContains(
			"TRANS Hello Paul good you are here. But it is late, see you\n",
			$parsedHtml, "Testing entity, default string and injection array"
		);

		$this->assertContains(
			"TRANS Hello Cat meow. But it is late, meow\n",
			$parsedHtml, "Testing a translation with just entity and injection array"
		);

		//test injected calls
		$this->assertContains(
			"TRANS Hello ".Director::absoluteBaseURL()." ".i18n::get_locale().". But it is late, global calls\n",
			$parsedHtml, "Testing a translation with just entity and injection array, but with global variables injected in"
		);

		i18n::set_locale($oldLocale);
	}
	
	function testGetLocaleFromLang() {
		$this->assertEquals('en_US', i18n::get_locale_from_lang('en'));
		$this->assertEquals('de_DE', i18n::get_locale_from_lang('de_DE'));
		$this->assertEquals('xy_XY', i18n::get_locale_from_lang('xy'));
	}
	
	function testValidateLocale() {
		$this->assertTrue(i18n::validate_locale('en_US'), 'Known locale in underscore format is valid');
		$this->assertTrue(i18n::validate_locale('en-US'), 'Known locale in dash format is valid');
		$this->assertFalse(i18n::validate_locale('en'), 'Short lang format is not valid');
		$this->assertFalse(i18n::validate_locale('xx_XX'), 'Unknown locale in correct format is not valid');
		$this->assertFalse(i18n::validate_locale(''), 'Empty string is not valid');
	}
	
	function testTranslate() {
		$oldLocale = i18n::get_locale();
		
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.ENTITY' => 'Entity with "Double Quotes"',
		), 'en_US');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.ENTITY' => 'Entity with "Double Quotes" (de)',
			'i18nTestModule.ADDITION' => 'Addition (de)',
		), 'de');
		i18n::get_translator('core')->getAdapter()->addTranslation(array(
			'i18nTestModule.ENTITY' => 'Entity with "Double Quotes" (de_AT)',
		), 'de_AT');
		
		
		$this->assertEquals(i18n::_t('i18nTestModule.ENTITY'), 'Entity with "Double Quotes"',
			'Returns translation in default language'
		);
		
		i18n::set_locale('de');
		$this->assertEquals(i18n::_t('i18nTestModule.ENTITY'), 'Entity with "Double Quotes" (de)',
			'Returns translation according to current locale'
		);
		
		i18n::set_locale('de_AT');
		$this->assertEquals(i18n::_t('i18nTestModule.ENTITY'), 'Entity with "Double Quotes" (de_AT)',
			'Returns specific regional translation if available'
		);
		$this->assertEquals(i18n::_t('i18nTestModule.ADDITION'), 'Addition (de)',
			'Returns fallback non-regional translation if regional is not available'
		);
		
		i18n::set_locale('fr');
		$this->assertEquals(i18n::_t('i18nTestModule.ENTITY'), '',
			'Returns empty translation without default string if locale is not found'
		);
		$this->assertEquals(i18n::_t('i18nTestModule.ENTITY', 'default'), 'default',
			'Returns default string if locale is not found'
		);
		
		i18n::set_locale($oldLocale);
	}
	
	function testIncludeByLocale() {
		// Looping through modules, so we can test the translation autoloading
		// Load non-exclusive to retain core class autoloading
		$classManifest = new SS_ClassManifest($this->alternateBasePath, true, true, false);
		SS_ClassLoader::instance()->pushManifest($classManifest);
		
		$adapter = i18n::get_translator('core')->getAdapter();
		$this->assertTrue($adapter->isAvailable('en'));
		$this->assertFalse($adapter->isAvailable('de'));
		$this->assertFalse($adapter->isTranslated('i18nTestModule.ENTITY', 'de'), 
			'Existing unloaded entity not available before call'
		);
		$this->assertFalse($adapter->isTranslated('i18nTestModule.ENTITY', 'af'), 
			'Non-existing unloaded entity not available before call'
		);

		i18n::include_by_locale('de');
		
		$this->assertTrue($adapter->isAvailable('en'));
		$this->assertTrue($adapter->isAvailable('de'));
		$this->assertTrue($adapter->isTranslated('i18nTestModule.ENTITY', null, 'de'), 'Includes module files');
		$this->assertTrue($adapter->isTranslated('i18nTestTheme1.LAYOUTTEMPLATE', null, 'de'), 'Includes theme files');
		$this->assertTrue($adapter->isTranslated('i18nTestModule.OTHERENTITY', null, 'de'), 'Includes submodule files');
		
		SS_ClassLoader::instance()->popManifest();
	}

	function testIncludeByLocaleWithoutFallbackLanguage() {
		$classManifest = new SS_ClassManifest($this->alternateBasePath, true, true, false);
		SS_ClassLoader::instance()->pushManifest($classManifest);
		
		$adapter = i18n::get_translator('core')->getAdapter();
		$this->assertTrue($adapter->isAvailable('en'));
		$this->assertFalse($adapter->isAvailable('mi')); // not defined at all
		$this->assertFalse($adapter->isAvailable('mi_NZ')); // defined, but not loaded yet
		$this->assertFalse($adapter->isTranslated('i18nTestModule.ENTITY', 'mi'), 
			'Existing unloaded entity not available before call'
		);
		$this->assertFalse($adapter->isTranslated('i18nTestModule.ENTITY', 'mi_NZ'), 
			'Non-existing unloaded entity not available before call'
		);

		i18n::include_by_locale('mi_NZ');
		
		$this->assertFalse($adapter->isAvailable('mi'));
		$this->assertTrue($adapter->isAvailable('mi_NZ'));
		$this->assertTrue($adapter->isTranslated('i18nTestModule.ENTITY', null, 'mi_NZ'), 'Includes module files');
		
		SS_ClassLoader::instance()->popManifest();
	}
	
	function testRegisterTranslator() {
		$translator = new Zend_Translate(array(
			'adapter' => 'i18nTest_CustomTranslatorAdapter',
			'disableNotices' => true,
		));
		
		i18n::register_translator($translator, 'custom', 10);
		$translators = i18n::get_translators();
		$this->assertArrayHasKey('custom', $translators[10]);
		$this->assertInstanceOf('Zend_Translate', $translators[10]['custom']);
		$this->assertInstanceOf('i18nTest_CustomTranslatorAdapter', $translators[10]['custom']->getAdapter());
		
		i18n::unregister_translator('custom');
		$translators = i18n::get_translators();
		$this->assertArrayNotHasKey('custom', $translators[10]);
	}
	
	function testMultipleTranslators() {
		// Looping through modules, so we can test the translation autoloading
		// Load non-exclusive to retain core class autoloading
		$classManifest = new SS_ClassManifest($this->alternateBasePath, true, true, false);
		SS_ClassLoader::instance()->pushManifest($classManifest);

		// Changed manifest, so we also need to unset all previously collected messages.
		// The easiest way to do this it to register a new adapter.
		$adapter = new Zend_Translate(array(
			'adapter' => 'i18nRailsYamlAdapter',
			'locale' => i18n::default_locale(),
			'disableNotices' => true,
		));
		i18n::register_translator($adapter, 'core');
		
		i18n::set_locale('en_US');

		$this->assertEquals(
			i18n::_t('i18nTestModule.ENTITY'),
			'Entity with "Double Quotes"'
		);
		$this->assertEquals(
			i18n::_t('AdapterEntity1', 'AdapterEntity1'),
			'AdapterEntity1',
			'Falls back to default string if not found'
		);
		
		// Add a new translator
		$translator = new Zend_Translate(array(
			'adapter' => 'i18nTest_CustomTranslatorAdapter',
			'disableNotices' => true,
		));
		i18n::register_translator($translator, 'custom', 11);
		$this->assertEquals(
			i18n::_t('i18nTestModule.ENTITY'),
			'i18nTestModule.ENTITY CustomAdapter (en_US)',
			'Existing entities overruled by adapter with higher priority'
		);
		$this->assertEquals(
			i18n::_t('AdapterEntity1', 'AdapterEntity1'),
			'AdapterEntity1 CustomAdapter (en_US)',
			'New entities only defined in new adapter are detected'
		);

		// Add a second new translator to test priorities
		$translator = new Zend_Translate(array(
			'adapter' => 'i18nTest_OtherCustomTranslatorAdapter',
			'disableNotices' => true,
		));
		i18n::register_translator($translator, 'othercustom_lower_prio', 5); 
		$this->assertEquals(
			i18n::_t('i18nTestModule.ENTITY'),
			'i18nTestModule.ENTITY CustomAdapter (en_US)',
			'Adapter with lower priority loses'
		);
		
		// Add a third new translator to test priorities
		$translator = new Zend_Translate(array(
			'adapter' => 'i18nTest_OtherCustomTranslatorAdapter',
			'disableNotices' => true,
		));
		
		i18n::register_translator($translator, 'othercustom_higher_prio', 15);

		$this->assertEquals(
			i18n::_t('i18nTestModule.ENTITY'),
			'i18nTestModule.ENTITY OtherCustomAdapter (en_US)',
			'Adapter with higher priority wins'
		);
		
		i18n::unregister_translator('custom');
		i18n::unregister_translator('othercustom_lower_prio');
		i18n::unregister_translator('othercustom_higher_prio');
		
		SS_ClassLoader::instance()->popManifest();
	}
	
}

class i18nTest_DataObject extends DataObject implements TestOnly {
	
	static $db = array(
		'MyProperty' => 'Varchar',
		'MyUntranslatedProperty' => 'Text'
	);
	
	static $has_one = array(
		'HasOneRelation' => 'Member'
	);
	
	static $has_many = array(
		'HasManyRelation' => 'Member'
	);
	
	static $many_many = array(
		'ManyManyRelation' => 'Member'
	);
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * 
	 */
	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['MyProperty'] = _t('i18nTest_DataObject.MyProperty', 'My Property');
		
		return $labels;
	}
	
}

class i18nTest_Object extends Object implements TestOnly, i18nEntityProvider {
	static $my_translatable_property = "Untranslated";
	
	static function my_translatable_property() {
		return _t("i18nTest_Object.my_translatable_property", self::$my_translatable_property);
	}
	
	function provideI18nEntities() {
		return array(
			"i18nTest_Object.my_translatable_property" => array(
				self::$my_translatable_property
			)
		);
	}
}

class i18nTest_CustomTranslatorAdapter extends Zend_Translate_Adapter implements TestOnly,i18nTranslateAdapterInterface {
	protected function _loadTranslationData($filename, $locale, array $options = array()) {
		return array(
			$locale => array(
				'AdapterEntity1' =>  'AdapterEntity1 CustomAdapter (' . $locale . ')',
				'i18nTestModule.ENTITY' => 'i18nTestModule.ENTITY CustomAdapter (' . $locale . ')',
			)
		);
	}
	
	function toString() {
		return 'i18nTest_CustomTranslatorAdapter';
	}
	
	function getFilenameForLocale($locale) {
		return false; // not file based
	}
}

class i18nTest_OtherCustomTranslatorAdapter extends Zend_Translate_Adapter implements TestOnly,i18nTranslateAdapterInterface {
	protected function _loadTranslationData($filename, $locale, array $options = array()) {
		return array(
			$locale => array(
				'i18nTestModule.ENTITY' => 'i18nTestModule.ENTITY OtherCustomAdapter (' . $locale . ')',
			)
		);
	}
	
	function toString() {
		return 'i18nTest_OtherCustomTranslatorAdapter';
	}
	
	function getFilenameForLocale($locale) {
		return false; // not file based
	}
}
