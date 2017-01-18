<?php

use SilverStripe\i18n\i18nEntityProvider;

class i18nProviderClass implements i18nEntityProvider
{
    public function provideI18nEntities()
    {
        return [
            'i18nProviderClass.TITLE' => 'My Provider Class',
            'i18nProviderClass.PLURALS' => [
                'one' => 'A class',
                'other' => '{count} classes',
            ],
            'i18nProviderClass.OTHER_MODULE' => [
                'default' => 'i18ntestmodule string defined in i18nothermodule',
                'module' => 'i18ntestmodule'
            ],
        ];
    }
}
