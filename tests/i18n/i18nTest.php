<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class i18nTest extends SapphireTest {
	
	/**
	 * @var string $tmpBasePath Used to write language files.
	 * We don't want to store them inside sapphire (or in any web-accessible place)
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
		
		$this->alternateBasePath = Director::baseFolder() . "/sapphire/tests/i18n/_fakewebroot";
		$this->alternateBaseSavePath = TEMP_FOLDER . '/i18nTextCollectorTest_webroot';
		FileSystem::makeFolder($this->alternateBaseSavePath);
		
		// SSViewer and ManifestBuilder don't support different webroots, hence we set the paths manually
		global $_CLASS_MANIFEST;
		$_CLASS_MANIFEST['i18nTestModule'] = $this->alternateBasePath . '/i18ntestmodule/code/i18nTestModule.php';
		$_CLASS_MANIFEST['i18nTestModule_Addition'] = $this->alternateBasePath . '/i18ntestmodule/code/i18nTestModule.php';
		$_CLASS_MANIFEST['i18nTestModuleDecorator'] = $this->alternateBasePath . '/i18nothermodule/code/i18nTestModuleDecorator.php';
		
		global $_ALL_CLASSES;
		$_ALL_CLASSES['parents']['i18nTestModule'] = array('DataObject'=>'DataObject','Object'=>'Object');
		$_ALL_CLASSES['parents']['i18nTestModule_Addition'] = array('Object'=>'Object');
		$_ALL_CLASSES['parents']['i18nTestModuleDecorator'] = array('DataObjectDecorator'=>'DataObjectDecorator','Object'=>'Object');

		global $_TEMPLATE_MANIFEST;
		$_TEMPLATE_MANIFEST['i18nTestModule.ss'] = array(
			'main' => $this->alternateBasePath . '/i18ntestmodule/templates/i18nTestModule.ss',
			'Layout' => $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss',
		);
		$_TEMPLATE_MANIFEST['i18nTestModuleInclude.ss'] = array(
			'Includes' => $this->alternateBasePath . '/i18ntestmodule/templates/Includes/i18nTestModuleInclude.ss',
		);
		
		$this->originalLocale = i18n::get_locale();
	}
	
	function tearDown() {
		//FileSystem::removeFolder($this->tmpBasePath);
		
		global $_CLASS_MANIFEST;
		unset($_CLASS_MANIFEST['i18nTestModule']);
		unset($_CLASS_MANIFEST['i18nTestModule_Addition']);
		
		global $_TEMPLATE_MANIFEST;
		unset($_TEMPLATE_MANIFEST['i18nTestModule.ss']);
		unset($_TEMPLATE_MANIFEST['i18nTestModuleInclude.ss']);
		
		i18n::set_locale($this->originalLocale);
		
		parent::tearDown();
	}
	
	function testDateFormatFromLocale() {
		i18n::set_locale('en_US');
		$this->assertEquals('MM/dd/yyyy', i18n::get_date_format());
		i18n::set_locale('en_NZ');
		$this->assertEquals('d/MM/yyyy', i18n::get_date_format());
		i18n::set_locale('en_US');
	}
	
	function testTimeFormatFromLocale() {
		i18n::set_locale('en_US');
		$this->assertEquals('hh:mm a', i18n::get_time_format());
		i18n::set_locale('de_DE');
		$this->assertEquals('HH:mm:ss', i18n::get_time_format());
		i18n::set_locale('en_US');
	}
	
	function testDateFormatCustom() {
		i18n::set_locale('en_US');
		$this->assertEquals('MM/dd/yyyy', i18n::get_date_format());
		i18n::set_date_format('d/MM/yyyy');
		$this->assertEquals('d/MM/yyyy', i18n::get_date_format());
	}
	
	function testTimeFormatCustom() {
		i18n::set_locale('en_US');
		$this->assertEquals('hh:mm a', i18n::get_time_format());
		i18n::set_time_format('HH:mm:ss');
		$this->assertEquals('HH:mm:ss', i18n::get_time_format());
	}
	
	function testGetExistingTranslations() {
		$translations = i18n::get_existing_translations();
		$this->assertTrue(isset($translations['en_US']), 'Checking for en_US translation');
		$this->assertTrue(isset($translations['de_DE']), 'Checking for de_DE translation');
	}
	
	function testDataObjectFieldLabels() {
		global $lang;
		$oldLocale = i18n::get_locale();
		i18n::set_locale('de_DE');
		$obj = new i18nTest_DataObject();
		
		$lang['en_US']['i18nTest_DataObject']['MyProperty'] = 'MyProperty';
		$lang['de_DE']['i18nTest_DataObject']['MyProperty'] = 'Mein Attribut';
	
		$this->assertEquals(
			$obj->fieldLabel('MyProperty'),
			'Mein Attribut'
		);
		
		$lang['en_US']['i18nTest_DataObject']['MyUntranslatedProperty'] = 'MyUntranslatedProperty';
		$this->assertEquals(
			$obj->fieldLabel('MyUntranslatedProperty'),
			'My Untranslated Property'
		);
		
		i18n::set_locale($oldLocale);
	}
	
	function testProvideI18nEntities() {
		global $lang;
		$oldLocale = i18n::get_locale();
		$lang['en_US']['i18nTest_Object']['my_translatable_property'] = 'Untranslated';
		$lang['de_DE']['i18nTest_Object']['my_translatable_property'] = 'Übersetzt';
		
		i18n::set_locale('en_US');
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
		global $lang;
		$oldLocale = i18n::get_locale();
		
		i18n::set_locale('en_US');
		$lang['en_US']['i18nTestModule']['MAINTEMPLATE'] = 'Main Template';
		$lang['en_US']['i18nTestModule.ss']['SPRINTFNONAMESPACE'] = 'My replacement no namespace: %s';
		$lang['en_US']['i18nTestModule']['LAYOUTTEMPLATE'] = 'Layout Template';
		$lang['en_US']['i18nTestModule.ss']['LAYOUTTEMPLATENONAMESPACE'] = 'Layout Template no namespace';
		$lang['en_US']['i18nTestModule']['SPRINTFNAMESPACE'] = 'My replacement: %s';
		$lang['en_US']['i18nTestModule']['WITHNAMESPACE'] = 'Include Entity with Namespace';
		$lang['en_US']['i18nTestModule.ss']['NONAMESPACE'] = 'Include Entity without Namespace';
		$lang['en_US']['i18nTestModuleInclude.ss']['SPRINTFINCLUDENAMESPACE'] = 'My include replacement: %s';
		$lang['en_US']['i18nTestModule.ss']['SPRINTFINCLUDENONAMESPACE'] = 'My include replacement no namespace: %s';
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
		$lang['de_DE']['i18nTestModule']['MAINTEMPLATE'] = 'TRANS Main Template';
		$lang['de_DE']['i18nTestModule.ss']['SPRINTFNONAMESPACE'] = 'TRANS My replacement no namespace: %s';
		$lang['de_DE']['i18nTestModule']['LAYOUTTEMPLATE'] = 'TRANS Layout Template';
		$lang['de_DE']['i18nTestModule.ss']['LAYOUTTEMPLATENONAMESPACE'] = 'TRANS Layout Template no namespace';
		$lang['de_DE']['i18nTestModule']['SPRINTFNAMESPACE'] = 'TRANS My replacement: %s';
		$lang['de_DE']['i18nTestModule']['WITHNAMESPACE'] = 'TRANS Include Entity with Namespace';
		$lang['de_DE']['i18nTestModuleInclude.ss']['NONAMESPACE'] = 'TRANS Include Entity without Namespace';
		$lang['de_DE']['i18nTestModuleInclude.ss']['SPRINTFINCLUDENAMESPACE'] = 'TRANS My include replacement: %s';
		$lang['de_DE']['i18nTestModule.ss']['SPRINTFINCLUDENONAMESPACE'] = 'TRANS My include replacement no namespace: %s';
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
	
	function testGetLocaleFromLang() {
		$this->assertEquals('en_US', i18n::get_locale_from_lang('en'));
		$this->assertEquals('de_DE', i18n::get_locale_from_lang('de_DE'));
		$this->assertEquals('xy_XY', i18n::get_locale_from_lang('xy'));
	}

	function testRegisteredPlugin() {
		global $lang;

		// save lang state, if we don't do this we may break other tests
		$oldLang = $lang;

		$lang = array(); // clear translations
		i18n::register_plugin("testPlugin", array("i18nTest", "translationTestPlugin"));

		// We have to simulate what include_by_locale() does, including loading translation provider data.
		$lang['en_US']["i18nTestProvider"]["foo"] = "bar_en";
		$lang['de_DE']["i18nTestProvider"]["foo"] = "bar_de";
		i18n::plugins_load('en_US');

		i18n::set_locale('en_US');
		$this->assertEquals(_t("i18nTestProvider.foo"), "baz_en");
		i18n::set_locale('de_DE');
		$this->assertEquals(_t("i18nTestProvider.foo"), "bar_de");
		i18n::unregister_plugin("testTranslator");

		$lang = $oldLang;
	}
	
	function testValidateLocale() {
		$this->assertTrue(i18n::validate_locale('en_US'), 'Known locale in underscore format is valid');
		$this->assertTrue(i18n::validate_locale('en-US'), 'Known locale in dash format is valid');
		$this->assertFalse(i18n::validate_locale('en'), 'Short lang format is not valid');
		$this->assertFalse(i18n::validate_locale('xx_XX'), 'Unknown locale in correct format is not valid');
		$this->assertFalse(i18n::validate_locale(''), 'Empty string is not valid');
	}

	static function translationTestPlugin($locale) {
		$result = array();
		$result["en_US"]["i18nTestProvider"]["foo"] = "baz_en";
		return $result;
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
?>
