<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\i18n\i18nTranslateAdapterInterface;
use Zend_Translate_Adapter;

class OtherCustomTranslatorAdapter extends Zend_Translate_Adapter implements TestOnly, i18nTranslateAdapterInterface
{
    protected function _loadTranslationData($filename, $locale, array $options = array())
    {
        return array(
            $locale => array(
                'i18nTestModule.ENTITY' => 'i18nTestModule.ENTITY OtherCustomAdapter (' . $locale . ')',
            )
        );
    }

    public function toString()
    {
        return 'i18nTest_OtherCustomTranslatorAdapter';
    }

    public function getFilenameForLocale($locale)
    {
        return false; // not file based
    }
}
