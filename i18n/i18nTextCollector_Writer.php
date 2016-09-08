<?php

namespace SilverStripe\i18n;

/**
 * Allows serialization of entity definitions collected through {@link i18nTextCollector}
 * into a persistent format, usually on the filesystem.
 */
interface i18nTextCollector_Writer
{
	/**
	 * @param array $entities Map of entity names (incl. namespace) to an numeric array, with at
	 * least one element, the original string, and an optional second element, the context.
	 * @param string $locale
	 * @param string $path The directory base on which the collector should create new lang folders
	 * and files. Usually the webroot set through {@link Director::baseFolder()}. Can be overwritten
	 * for testing or export purposes.
	 * @return bool success
	 */
	public function write($entities, $locale, $path);
}
