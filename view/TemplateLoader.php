<?php

namespace SilverStripe\View;

/**
 * Handles finding templates from a stack of template manifest objects.
 *
 * @package framework
 * @subpackage view
 */
class TemplateLoader {

	/**
	 * @var TemplateLoader
	 */
	private static $instance;

	protected $base;

	protected $sets = [];

	public static function instance() {
		return self::$instance ? self::$instance : self::$instance = new self();
	}

	public static function set_instance(TemplateLoader $instance) {
		self::$instance = $instance;
	}

	public function __construct($base = null) {
		$this->base = $base ? $base : BASE_PATH;
	}

	public function addSet($set, $manifest) {
		$this->sets[$set] = $manifest;
	}

	public function getPath($identifier) {
		$slashPos = strpos($identifier, '/');

		// If identifier starts with "/", it's a path from root
		if ($slashPos === 0) {
			return substr($identifier, 1);
		}
		// Otherwise if there is a "/", identifier is a vendor'ed module
		elseif ($slashPos !== false) {
			$parts = explode(':', $identifier, 2);

			list($vendor, $module) = explode('/', $parts[0], 2);
			$theme = count($parts) > 1 ? $parts[1] : '';

			$path = $module . ($theme ? '/themes/'.$theme : '');

			// Right now we require $module to be a silverstripe module (in root) or theme (in themes dir)
			// If both exist, we prefer theme
			if (is_dir(THEMES_PATH . '/' .$path)) {
				return THEMES_DIR . '/' . $path;
			}
			else {
				return $path;
			}
		}
		// Otherwise it's a (deprecated) old-style "theme" identifier
		else {
			return THEMES_DIR.'/'.$identifier;
		}
	}

	/**
	 * Attempts to find possible candidate templates from a set of template
	 * names from modules, current theme directory and finally the application
	 * folder.
	 *
	 * The template names can be passed in as plain strings, or be in the
	 * format "type/name", where type is the type of template to search for
	 * (e.g. Includes, Layout).
	 *
	 * @param  string|array $templates
	 * @param  string $theme
	 *
	 * @return array
	 */
	public function findTemplate($template, $themes = []) {

		if(is_array($template)) {
			$type = array_key_exists('type', $template) ? $template['type'] : '';
			$templateList = array_key_exists('templates', $template) ? $template['templates'] : $template;
		}
		else {
			$type = '';
			$templateList = array($template);
		}

		if(count($templateList) == 1 && substr($templateList[0], -3) == '.ss') {
			return $templateList[0];
		}

		foreach($templateList as $i => $template) {
			$template = str_replace('\\', '/', $template);
			$parts = explode('/', $template);

			$tail = array_pop($parts);
			$head = implode('/', $parts);

			foreach($themes as $themename) {
				$subthemes = isset($this->sets[$themename]) ? $this->sets[$themename]->getThemes() : [$themename];

				foreach($subthemes as $theme) {
					$themePath = $this->base . '/' . $this->getPath($theme);

					$path = $themePath . '/templates/' . implode('/', array_filter([$head, $type, $tail])) . '.ss';
					if (file_exists($path)) return $path;
				}
			}
		}
	}

}
