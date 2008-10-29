<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class i18nTest extends SapphireTest {
	function testGetExistingTranslations() {
		$translations = i18n::get_existing_translations();
		$this->assertTrue(isset($translations['en_US']), 'Checking for en_US translation');
		$this->assertTrue(isset($translations['de_DE']), 'Checking for de_DE translation');
	}
	
	function testDataObjectFieldLabels() {
		global $lang;
		$oldLocale = i18n::get_locale();
		i18n::set_locale('de_DE');
		$obj = new i18nTest_Object();
		
		$lang['en_US']['i18nTest_Object']['db_MyProperty'] = 'MyProperty';
		$lang['de_DE']['i18nTest_Object']['db_MyProperty'] = 'Mein Attribut';
		$this->assertEquals(
			$obj->fieldLabel('MyProperty'),
			'Mein Attribut'
		);
		
		$lang['en_US']['i18nTest_Object']['has_one_HasOneRelation'] = 'HasOneRelation';
		$lang['de_DE']['i18nTest_Object']['has_one_HasOneRelation'] = 'Eins zu eins';
		$this->assertEquals(
			$obj->fieldLabel('HasOneRelation'),
			'Eins zu eins'
		);
		
		$lang['en_US']['i18nTest_Object']['has_many_HasManyRelation'] = 'HasManyRelation';
		$lang['de_DE']['i18nTest_Object']['has_many_HasManyRelation'] = 'Viel zu eins';
		$this->assertEquals(
			$obj->fieldLabel('HasManyRelation'),
			'Viel zu eins'
		);
		
		$lang['en_US']['i18nTest_Object']['many_many_ManyManyRelation'] = 'ManyManyRelation';
		$lang['de_DE']['i18nTest_Object']['many_many_ManyManyRelation'] = 'Viel zu viel';
		$this->assertEquals(
			$obj->fieldLabel('ManyManyRelation'),
			'Viel zu viel'
		);
		
		$lang['en_US']['i18nTest_Object']['db_MyUntranslatedProperty'] = 'MyUntranslatedProperty';
		$this->assertEquals(
			$obj->fieldLabel('MyUntranslatedProperty'),
			'My Untranslated Property'
		);
		
		i18n::set_locale($oldLocale);
	}
	
}

class i18nTest_Object extends DataObject implements TestOnly {
	
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
	
}
?>
