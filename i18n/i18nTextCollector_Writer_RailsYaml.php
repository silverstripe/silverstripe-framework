<?php

namespace SilverStripe\i18n;

use SilverStripe\Assets\Filesystem;
use Symfony\Component\Yaml\Dumper;
use LogicException;

/**
 * Writes files compatible with {@link i18nRailsYamlAdapter}.
 */
class i18nTextCollector_Writer_RailsYaml implements i18nTextCollector_Writer
{

	public function write($entities, $locale, $path)
	{
		// Create folder for lang files
		$langFolder = $path . '/lang';
		if (!file_exists($langFolder)) {
			Filesystem::makeFolder($langFolder);
			touch($langFolder . '/_manifest_exclude');
		}

		// Open the English file and write the Master String Table
		$langFile = $langFolder . '/' . $locale . '.yml';
		if ($fh = fopen($langFile, "w")) {
			fwrite($fh, $this->getYaml($entities, $locale));
			fclose($fh);
		} else {
			throw new LogicException("Cannot write language file! Please check permissions of $langFile");
		}

		return true;
	}

	public function getYaml($entities, $locale)
	{
		// Unflatten array
		$entitiesNested = array();
		foreach ($entities as $entity => $spec) {
			// Legacy support: Don't count *.ss as namespace
			$entity = preg_replace('/\.ss\./', '___ss.', $entity);
			$parts = explode('.', $entity);
			$currLevel = &$entitiesNested;
			while ($part = array_shift($parts)) {
				$part = str_replace('___ss', '.ss', $part);
				if (!isset($currLevel[$part])) {
					$currLevel[$part] = array();
				}
				$currLevel = &$currLevel[$part];
			}
			$currLevel = $spec[0];
		}

		// Write YAML
		$dumper = new Dumper();
		$dumper->setIndentation(2);
		// TODO Dumper can't handle YAML comments, so the context information is currently discarded
		$result = $dumper->dump(array($locale => $entitiesNested), 99);
		return $result;
	}
}
