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
	
	function fieldLabels() {
		$labels = parent::fieldLabels();
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
