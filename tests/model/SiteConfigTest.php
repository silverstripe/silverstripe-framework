<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * Note: Most of the permission-related SiteConfig tests are located in 
 * SiteTreePermissionsTest
 */
class SiteConfigTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/model/SiteConfigTest.yml';
	
	protected $requiredExtensions = array(
		'SiteTree' => array('Translatable'),
		'SiteConfig' => array('Translatable'),
	);
	
	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites')
	);
	
	private $origLocale;

	function setUp() {
		parent::setUp();
				
		$this->origLocale = Translatable::default_locale();
		Translatable::set_default_locale("en_US");
	}
	
	function tearDown() {
		Translatable::set_default_locale($this->origLocale);
		Translatable::set_current_locale($this->origLocale);

		parent::tearDown();
	}
	
	function testCurrentCreatesDefaultForLocale() {
		$configEn = SiteConfig::current_site_config();
		$configFr = SiteConfig::current_site_config('fr_FR');
		
		$this->assertType('SiteConfig', $configFr);
		$this->assertEquals($configFr->Locale, 'fr_FR');
		$this->assertEquals($configFr->Title, $configEn->Title, 'Copies title from existing config');
	}
	
	function testCanEditTranslatedRootPages() {
		$configEn = $this->objFromFixture('SiteConfig', 'en_US');
		$configDe = $this->objFromFixture('SiteConfig', 'de_DE');
		
		$pageEn = $this->objFromFixture('Page', 'root_en');
		$pageDe = $pageEn->createTranslation('de_DE');
		
		$translatorDe = $this->objFromFixture('Member', 'translator_de');
		$translatorEn = $this->objFromFixture('Member', 'translator_en');
		
		$this->assertFalse($pageEn->canEdit($translatorDe));
		$this->assertTrue($pageEn->canEdit($translatorEn));
	}

	function testAvailableThemes() {
		$config = SiteConfig::current_site_config();
		$ds = DIRECTORY_SEPARATOR;
		$testThemeBaseDir = TEMP_FOLDER . $ds . 'test-themes';
		
		if(file_exists($testThemeBaseDir)) Filesystem::removeFolder($testThemeBaseDir);
		mkdir($testThemeBaseDir);
		mkdir($testThemeBaseDir . $ds . 'blackcandy');
		mkdir($testThemeBaseDir . $ds . 'blackcandy_blog');
		mkdir($testThemeBaseDir . $ds . 'darkshades');
		mkdir($testThemeBaseDir . $ds . 'darkshades_blog');
		
		$themes = $config->getAvailableThemes($testThemeBaseDir);
		$this->assertContains('blackcandy', $themes, 'Test themes contain blackcandy theme');
		$this->assertContains('darkshades', $themes, 'Test themes contain darkshades theme');
		
		SiteConfig::disable_theme('darkshades');
		$themes = $config->getAvailableThemes($testThemeBaseDir);
		$this->assertFalse(in_array('darkshades', $themes), 'Darkshades was disabled - it is no longer available');
		
		Filesystem::removeFolder($testThemeBaseDir);
	}
	
}
?>