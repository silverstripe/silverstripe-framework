<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\Core\Object;
use SilverStripe\Dev\TestOnly;
use SilverStripe\i18n\i18nEntityProvider;

class TestObject extends Object implements TestOnly, i18nEntityProvider
{
	static $my_translatable_property = "Untranslated";

	public static function my_translatable_property()
	{
		return _t("i18nTest_Object.my_translatable_property", self::$my_translatable_property);
	}

	public function provideI18nEntities()
	{
		return array(
			"i18nTest_Object.my_translatable_property" => array(
				self::$my_translatable_property
			)
		);
	}
}
