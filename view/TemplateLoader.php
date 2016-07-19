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

	/**
	 * @var ThemeManifest[]
	 */
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

	/**
	 * Add a new theme manifest for a given identifier. E.g. '$default'
	 *
	 * @param string $set
	 * @param ThemeManifest $manifest
	 */
	public function addSet($set, $manifest) {
		$this->sets[$set] = $manifest;
	}

	/**
	 * Given a theme identifier, determine the path from the root directory
	 *
	 * The mapping from $identifier to path follows these rules:
	 * - A simple theme name ('mytheme') which maps to the standard themes dir (/themes/mytheme)
	 * - A theme path with a leading slash ('/mymodule/themes/mytheme') which maps directly to that path.
	 * - or a vendored theme path. (vendor/mymodule:mytheme) which maps to the nested 'theme' within
	 *   that module. ('/mymodule/themes/mytheme').
	 * - A vendored module with no nested theme (vendor/mymodule) which maps to the root directory
	 *   of that module. ('/mymodule').
	 *
	 * @param string $identifier Theme identifier.
	 * @return string Path from root, not including leading forward slash. E.g. themes/mytheme
	 */
	public function getPath($identifier) {
		$slashPos = strpos($identifier, '/');

		// If identifier starts with "/", it's a path from root
		if ($slashPos === 0) {
			return substr($identifier, 1);
		}
		// Otherwise if there is a "/", identifier is a vendor'ed module
		elseif ($slashPos !== false) {
			// Extract from <vendor>/<module>:<theme> format.
			// <vendor> is optional, and if <theme> is omitted it defaults to the module root dir.
			// If <theme> is included, this is the name of the directory under moduleroot/themes/
			// which contains the theme.
			// <module> is always the name of the install directory, not necessarily the composer name.
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
	 * @param string|array $template Template name, or template spec in array format with the keys
	 * 'type' (type string) and 'templates' (template hierarchy in order of precedence).
	 * If 'templates' is ommitted then any other item in the array will be treated as the template
	 * list.
	 * Templates with an .ss extension will be treated as file paths, and will bypass
	 * theme-coupled resolution.
	 * @param array $themes List of themes to use to resolve themes. In most cases
	 * you should pass in {@see SSViewer::get_themes()}
	 * @return string Path to resolved template file, or null if not resolved.
	 */
	public function findTemplate($template, $themes) {
		$type = '';
		if(is_array($template)) {
			// Check if templates has type specified
			if (array_key_exists('type', $template)) {
				$type = $template['type'];
				unset($template['type']);
			}
			// Templates are either nested in 'templates' or just the rest of the list
			$templateList = array_key_exists('templates', $template) ? $template['templates'] : $template;
		} else {
			$templateList = array($template);
		}

		// If we have an .ss extension, this is a path, not a template name. We should
		// pass in templates without extensions in order for template manifest to find
		// files dynamically.
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

		// No template found
		return null;
	}

}
