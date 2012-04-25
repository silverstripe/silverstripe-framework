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
	protected $inited;
	protected $forceRegen;
	protected $templates = array();

	/**
	 * Constructs a new template manifest. The manifest is not actually built
	 * or loaded from cache until needed.
	 *
	 * @param string $base The base path.
	 * @param bool $includeTests Include tests in the manifest.
	 * @param bool $forceRegen Force the manifest to be regenerated.
	 */
	public function __construct($base, $includeTests = false, $forceRegen = false) {
		$this->base  = $base;
		$this->tests = $includeTests;

		$this->cacheKey   = $this->tests ? 'manifest_tests' : 'manifest';
		$this->forceRegen = $forceRegen;

		$this->cache = SS_Cache::factory('SS_TemplateManifest', 'Core', array(
			'automatic_serialization' => true,
			'lifetime' => null
		));
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
		if (strpos($pathname, $this->base . '/' . THEMES_DIR) === 0) {
			$start = strlen($this->base . '/' . THEMES_DIR) + 1;
			$theme = substr($pathname, $start);
			$theme = substr($theme, 0, strpos($theme, '/'));
			$theme = strtok($theme, '_');
		} else {
			$theme = null;
		}

		$type = basename(dirname($pathname));
		$name = strtolower(substr($basename, 0, -3));

		if ($type == self::TEMPLATES_DIR) {
			$type = 'main';
		}

		if ($theme) {
			$this->templates[$name]['themes'][$theme][$type] = $pathname;
		} else {
			$this->templates[$name][$type] = $pathname;
		}
	}

	protected function init() {
		if (!$this->forceRegen && $data = $this->cache->load($this->cacheKey)) {
			$this->templates = $data;
			$this->inited    = true;
		} else {
			$this->regenerate();
		}
	}

}
