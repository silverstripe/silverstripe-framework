<?php
/**
 * An extension to the default file finder with some extra filters to faciliate
 * autoload and template manifest generation:
 *   - Only modules with _config.php files are scanned.
 *   - If a _manifest_exclude file is present inside a directory it is ignored.
 *   - Assets and module language directories are ignored.
 *   - Module tests directories are skipped if the ignore_tests option is not
 *     set to false.
 *
 * @package framework
 * @subpackage manifest
 */
class ManifestFileFinder extends SS_FileFinder {

	const CONFIG_FILE  = '_config.php';
	const CONFIG_DIR  = '_config';
	const EXCLUDE_FILE = '_manifest_exclude';
	const LANG_DIR     = 'lang';
	const TESTS_DIR    = 'tests';

	protected static $default_options = array(
		'include_themes' => false,
		'ignore_tests'   => true,
		'min_depth'      => 1
	);

	public function acceptDir($basename, $pathname, $depth) {
		// Skip over the assets directory in the site root.
		if ($depth == 1 && $basename == ASSETS_DIR) {
			return false;
		}

		// Skip over any lang directories in the top level of the module.
		if ($depth == 2 && $basename == self::LANG_DIR) {
			return false;
		}

		// If we're not in testing mode, then skip over the tests directory in
		// the module root.
		if ($this->getOption('ignore_tests') && $depth == 2 && $basename == self::TESTS_DIR) {
			return false;
		}

		// Ignore any directories which contain a _manifest_exclude file.
		if (file_exists($pathname . '/' . self::EXCLUDE_FILE)) {
			return false;
		}

		// Only include top level module directories which have a configuration
		// _config.php file. However, if we're in themes mode then include
		// the themes dir without a config file.
		$lackingConfig = (
			$depth == 1
			&& !($this->getOption('include_themes') && $basename == THEMES_DIR)
			&& !file_exists($pathname . '/' . self::CONFIG_FILE)
			&& !file_exists($pathname . '/' . self::CONFIG_DIR)
		);

		if ($lackingConfig) {
			return false;
		}

		return parent::acceptDir($basename, $pathname, $depth);
	}

}
