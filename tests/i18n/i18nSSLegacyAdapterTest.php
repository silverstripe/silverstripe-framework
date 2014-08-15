<?php
/**
 * @package framework
 * @subpackage i18n
 */

class i18nSSLegacyAdapterTest extends SapphireTest {

	public function setUp() {
		parent::setUp();

		$this->alternateBasePath = $this->getCurrentAbsolutePath() . "/_fakewebroot";
		$this->alternateBaseSavePath = TEMP_FOLDER . '/i18nTextCollectorTest_webroot';
		Filesystem::makeFolder($this->alternateBaseSavePath);
		Config::inst()->update('Director', 'alternate_base_folder', $this->alternateBasePath);

		// Push a template loader running from the fake webroot onto the stack.
		$templateManifest = new SS_TemplateManifest($this->alternateBasePath, null, false, true);
		$templateManifest->regenerate(false);
		SS_TemplateLoader::instance()->pushManifest($templateManifest);
		$this->_oldTheme = Config::inst()->get('SSViewer', 'theme');
		Config::inst()->update('SSViewer', 'theme', 'testtheme1');

		$classManifest = new SS_ClassManifest($this->alternateBasePath, false, true, false);
		SS_ClassLoader::instance()->pushManifest($classManifest);

		$this->originalLocale = i18n::get_locale();

		// Override default adapter to avoid cached translations between tests.
		// Emulates behaviour in i18n::get_translators()
		$this->origAdapter = i18n::get_translator('core');
		$adapter = new Zend_Translate(array(
			'adapter' => 'i18nSSLegacyAdapter',
			'locale' => i18n::default_locale(),
			'disableNotices' => true,
		));
		i18n::register_translator($adapter, 'core');
		$adapter->removeCache();
		i18n::include_by_locale('en');
	}

	public function tearDown() {
		SS_TemplateLoader::instance()->popManifest();
		SS_ClassLoader::instance()->popManifest();
		i18n::set_locale($this->originalLocale);
		Config::inst()->update('Director', 'alternate_base_folder', null);
		Config::inst()->update('SSViewer', 'theme', $this->_oldTheme);
		i18n::register_translator($this->origAdapter, 'core');

		parent::tearDown();
	}

	public function testTranslate() {
		i18n::set_locale('en_US');
		$this->assertEquals(
			'Legacy translation',
			// defined in i18nothermodule/lang/en_US.php
			i18n::_t('i18nOtherModule.LEGACY'),
			'Finds original strings in PHP module files'
		);
		$this->assertEquals(
			'Legacy translation',
			// defined in themes/testtheme1/lang/en_US.php
			i18n::_t('i18nOtherModule.LEGACYTHEME'),
			'Finds original strings in theme files'
		);
		i18n::set_locale('de_DE');
		$this->assertEquals(
			'Legacy translation (de_DE)',
			// defined in i18nothermodule/lang/de_DE.php
			i18n::_t('i18nOtherModule.LEGACY'),
			'Finds translations in PHP module files'
		);
		$this->assertEquals(
			'Legacy translation (de_DE)',
			// defined in themes/testtheme1/lang/de_DE.php
			i18n::_t('i18nOtherModule.LEGACYTHEME'),
			'Finds original strings in theme files'
		);
		// TODO Implement likely subtags solution
		// i18n::set_locale('de');
		// $this->assertEquals(
		// 	'Legacy translation (de_DE)',
		// 	// defined in i18nothermodule/lang/de_DE.php
		// 	i18n::_t('i18nOtherModule.LEGACY'),
		// 	'Finds translations in PHP module files if only language locale is set'
		// );
	}

}
