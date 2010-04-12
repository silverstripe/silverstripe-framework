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
	
}
?>