<?php
/**
 * Handles finding templates from a stack of template manifest objects.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_TemplateLoader {

	/**
	 * @var SS_TemplateLoader
	 */
	private static $instance;

	/**
	 * @var SS_TemplateManifest[]
	 */
	protected $manifests = array();

	/**
	 * @return SS_TemplateLoader
	 */
	public static function instance() {
		return self::$instance ? self::$instance : self::$instance = new self();
	}

	/**
	 * Returns the currently active template manifest instance.
	 *
	 * @return SS_TemplateManifest
	 */
	public function getManifest() {
		return $this->manifests[count($this->manifests) - 1];
	}

	/**
	 * @param SS_TemplateManifest $manifest
	 */
	public function pushManifest(SS_TemplateManifest $manifest) {
		$this->manifests[] = $manifest;
	}

	/**
	 * @return SS_TemplateManifest
	 */
	public function popManifest() {
		return array_pop($this->manifests);
	}

	/**
	 * Attempts to find possible candidate templates from a set of template
	 * names and a theme.
	 *
	 * The template names can be passed in as plain strings, or be in the
	 * format "type/name", where type is the type of template to search for
	 * (e.g. Includes, Layout).
	 *
	 * @param  string|array $templates
	 * @param  string $theme
	 * @return array
	 */
	public function findTemplates($templates, $theme = null) {
		$result = array();

		foreach ((array) $templates as $template) {
			$found = false;

			if (strpos($template, '/')) {
				list($type, $template) = explode('/', $template, 2);
			} else {
				$type = null;
			}

			if ($candidates = $this->getManifest()->getTemplate($template)) {
				if ($theme && isset($candidates['themes'][$theme])) {
					$found = $candidates['themes'][$theme];
				} else {
					unset($candidates['themes']);
					$found = $candidates;
				}

				if ($found) {
					if ($type && isset($found[$type])) {
						$found = array('main' => $found[$type]);
					}

					$result = array_merge($found, $result);
				}
			}
		}

		return $result;
	}

}
