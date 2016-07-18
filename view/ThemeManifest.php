<?php

namespace SilverStripe\View;

use \ManifestFileFinder;

/**
 * A class which builds a manifest of all themes (which is really just a directory called "templates")
 *
 * @package framework
 * @subpackage manifest
 */
class ThemeManifest {

	const TEMPLATES_DIR = 'templates';

	protected $base;
	protected $tests;
	protected $project;

	protected $cache;
	protected $cacheKey;

	protected $themes = null;

	/**
	 * Constructs a new template manifest. The manifest is not actually built
	 * or loaded from cache until needed.
	 *
	 * @param string $base The base path.
	 * @param string $project Path to application code
	 *
	 * @param bool $includeTests Include tests in the manifest.
	 * @param bool $forceRegen Force the manifest to be regenerated.
	 */
	public function __construct($base, $project, $includeTests = false, $forceRegen = false) {
		$this->base  = $base;
		$this->tests = $includeTests;

		$this->project = $project;

		$cacheClass = defined('SS_MANIFESTCACHE') ? SS_MANIFESTCACHE : 'ManifestCache_File';

		$this->cache = new $cacheClass('thememanifest'.($includeTests ? '_tests' : ''));
		$this->cacheKey = $this->getCacheKey();

		if ($forceRegen) {
			$this->regenerate();
		}
	}

	/**
	 * @return string
	 */
	public function getBase() {
		return $this->base;
	}

	/**
	 * Generate a unique cache key to avoid manifest cache collisions.
	 * We compartmentalise based on the base path, the given project, and whether
	 * or not we intend to include tests.
	 * @return string
	 */
	public function getCacheKey() {
		return sha1(sprintf(
			"manifest-%s-%s-%u",
			$this->base,
			$this->project,
			$this->tests
		));
	}

	/**
	 * Returns a map of all themes information. The map is in the following format:
	 *
	 * <code>
	 *   [
	 *     'mysite',
	 *     'framework',
	 *     'framework/admin'
	 *   ]
	 * </code>
	 *
	 * @return array
	 */
	public function getThemes() {
		if ($this->themes === null) $this->init();
		return $this->themes;
	}

	/**
	 * Regenerates the manifest by scanning the base path.
	 *
	 * @param bool $cache
	 */
	public function regenerate($cache = true) {
		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'include_themes' => false,
			'ignore_dirs' => array('node_modules', THEMES_DIR),
			'ignore_tests'  => !$this->tests,
			'dir_callback'  => array($this, 'handleDirectory')
		));

		$this->themes = [];
		$finder->find($this->base);

		if ($cache) {
			$this->cache->save($this->themes, $this->cacheKey);
		}
	}

	public function handleDirectory($basename, $pathname, $depth)
	{
		if ($basename == self::TEMPLATES_DIR) {
			// We only want part of the full path, so split into directories
			$parts = explode('/', $pathname);
			// Take the end (the part relative to base), except the very last directory
			$themeParts = array_slice($parts, -$depth, $depth-1);
			// Then join again
			$path = '/'.implode('/', $themeParts);

			// If this is in the project, add to beginning of list. Else add to end.
			if ($themeParts[0] == $this->project) {
				array_unshift($this->themes, $path);
			}
			else {
				array_push($this->themes, $path);
			}
		}
	}

	protected function init() {
		if ($data = $this->cache->load($this->cacheKey)) {
			$this->themes = $data;
		} else {
			$this->regenerate();
		}
	}
}
