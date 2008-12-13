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
		self::create_temp_db();
		
		parent::setUp();
	}
	
	function tearDown() {
		if(!$this->origTranslatableSettings['enabled']) Translatable::disable();
		Translatable::set_default_lang($this->origTranslatableSettings['default_lang']);
		
		self::kill_temp_db();
		self::create_temp_db();
		
		parent::tearDown();
	}
	
	function testUpdateCMSFieldsOnSiteTree() {
		$pageOrigLang = $this->objFromFixture('Page', 'home');
		
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
		$pageTranslated = Translatable::get_one_by_lang('Page',"fr", "ID = $pageOrigLang->ID");
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