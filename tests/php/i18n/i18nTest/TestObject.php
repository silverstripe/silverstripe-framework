<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\i18n\i18nEntityProvider;

class TestObject implements TestOnly, i18nEntityProvider
{
    static $my_translatable_property = "Untranslated";

    public static function my_translatable_property()
    {
        return _t(__CLASS__ . ".my_translatable_property", self::$my_translatable_property);
    }

    public function provideI18nEntities()
    {
        return [
            __CLASS__ . ".my_translatable_property" => self::$my_translatable_property,
        ];
    }
}
