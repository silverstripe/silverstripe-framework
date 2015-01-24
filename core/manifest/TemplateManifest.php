<?php
/**
 * A class which builds a manifest of all templates present in a directory,
 * in both modules and themes.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_TemplateManifest {

	const TEMPLATES_DIR = 'templates';

	protected $base;
	protected $tests;
	protected $cache;
	protected $cacheKey;
	protected $project;
	protected $inited;
	protected $templates = array();

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

		$this->cache = new $cacheClass('templatemanifest'.($includeTests ? '_tests' : ''));
		$this->cacheKey = $this->getCacheKey($includeTests);
		
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
	 * @param boolean $includeTests
	 * @return string
	 */
	public function getCacheKey($includeTests = false) {
		return sha1(sprintf(
			"manifest-%s-%s-%s",
				$this->base,
				$this->project,
				(int) $includeTests // cast true to 1, false to 0
			)
		);
	}

	/**
	 * Returns a map of all template information. The map is in the following
	 * format:
	 *
	 * <code>
	 *   array(
	 *     'moduletemplate' => array(
	 *       'main' => '/path/to/module/templates/Main.ss'
	 *     ),
	 *     'include' => array(
	 *       'include' => '/path/to/module/templates/Includes/Include.ss'
	 *     ),
	 *     'page' => array(
	 *       'themes' => array(
	 *         'simple' => array(
	 *           'main'   => '/path/to/theme/Page.ss'
	 *           'Layout' => '/path/to/theme/Layout/Page.ss'
	 *         )
	 *       )
	 *     )
	 *   )
	 * </code>
	 *
	 * @return array
	 */
	public function getTemplates() {
		if (!$this->inited) {
			$this->init();
		}

		return $this->templates;
	}

	/**
	 * Returns a set of possible candidate templates that match a certain
	 * template name.
	 *
	 * This is the same as extracting an individual array element from
	 * {@link SS_TemplateManifest::getTemplates()}.
	 *
	 * @param  string $name
	 * @return array
	 */
	public function getTemplate($name) {
		if (!$this->inited) {
			$this->init();
		}

		$name = strtolower($name);

		if (array_key_exists($name, $this->templates)) {
			return $this->templates[$name];
		} else {
			return array();
		}
	}

	/**
	 * Returns the correct candidate template. In order of importance, application
	 * project code, current theme and finally modules.
	 *
	 * @param string $name
	 * @param string $theme - theme name
	 *
	 * @return array
	 */
	public function getCandidateTemplate($name, $theme = null) {
		$found = array();
		$candidates = $this->getTemplate($name);

		// theme overrides modules
		if ($theme && isset($candidates['themes'][$theme])) {
			$found = array_merge($candidates, $candidates['themes'][$theme]);
		}
		// project overrides theme
		if ($this->project && isset($candidates[$this->project])) {
			$found = array_merge($found, $candidates[$this->project]);
		}

		$found = ($found) ? $found : $candidates;

		if (isset($found['themes'])) unset($found['themes']);
		if (isset($found[$this->project])) unset($found[$this->project]);

		return $found;
	}

	/**
	 * Regenerates the manifest by scanning the base path.
	 *
	 * @param bool $cache
	 */
	public function regenerate($cache = true) {
		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'     => '/\.ss$/',
			'include_themes' => true,
			'ignore_tests'  => !$this->tests,
			'file_callback'  => array($this, 'handleFile')
		));

		$finder->find($this->base);

		if ($cache) {
			$this->cache->save($this->templates, $this->cacheKey);
		}

		$this->inited = true;
	}

	public function handleFile($basename, $pathname, $depth) {
		$projectFile = false;
		$theme = null;

		if (strpos($pathname, $this->base . '/' . THEMES_DIR) === 0) {
			$start = strlen($this->base . '/' . THEMES_DIR) + 1;
			$theme = substr($pathname, $start);
			$theme = substr($theme, 0, strpos($theme, '/'));
			$theme = strtok($theme, '_');
		} else if($this->project && (strpos($pathname, $this->base . '/' . $this->project .'/') === 0)) {
			$projectFile = true;
		}

		$type = basename(dirname($pathname));
		$name = strtolower(substr($basename, 0, -3));

		if ($type == self::TEMPLATES_DIR) {
			$type = 'main';
		}

		if ($theme) {
			$this->templates[$name]['themes'][$theme][$type] = $pathname;
		} else if($projectFile) {
			$this->templates[$name][$this->project][$type] = $pathname;
		} else {
			$this->templates[$name][$type] = $pathname;
		}

	}

	protected function init() {
		if ($data = $this->cache->load($this->cacheKey)) {
			$this->templates = $data;
			$this->inited    = true;
		} else {
			$this->regenerate();
		}
	}
}
