<?php

namespace SilverStripe\i18n\Messages;

/**
 * Allows serialization of entity definitions collected through {@link i18nTextCollector}
 * into a persistent format, usually on the filesystem.
 */
interface Writer
{
    /**
     * @param array $messages Map of entity names (incl. namespace) to default values. Values
     * may be array format for pluralised values, or strings for normal localisations.
     * @param string $locale
     * @param string $path The directory base on which the collector should create new lang folders
     * and files. Usually the webroot set through {@link Director::baseFolder()}. Can be overwritten
     * for testing or export purposes.
     */
    public function write($messages, $locale, $path);
}
