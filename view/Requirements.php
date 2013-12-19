<?php

/**
 * Requirements tracker, for javascript and css.
 * @todo Document the requirements tracker, and discuss it with the others.
 *
 * @package framework
 * @subpackage view
 */
class Requirements {

	/**
	 * Enable combining of css/javascript files.
	 * @param boolean $enable
	 */
	public static function set_combined_files_enabled($enable) {
		self::backend()->set_combined_files_enabled($enable);
	}

	/**
	 * Checks whether combining of css/javascript files is enabled.
	 * @return boolean
	 */
	public static function get_combined_files_enabled() {
		return self::backend()->get_combined_files_enabled();
	}

	/**
	 * Set the relative folder e.g. "assets" for where to store combined files
	 * @param string $folder Path to folder
	 */
	public static function set_combined_files_folder($folder) {
		self::backend()->setCombinedFilesFolder($folder);
	}

	/**
	 * Set whether we want to suffix requirements with the time /
	 * location on to the requirements
	 *
	 * @param bool
	 */
	public static function set_suffix_requirements($var) {
		self::backend()->set_suffix_requirements($var);
	}

	/**
	 * Return whether we want to suffix requirements
	 *
	 * @return bool
	 */
	public static function get_suffix_requirements() {
		return self::backend()->get_suffix_requirements();
	}

	/**
	 * Instance of requirements for storage
	 *
	 * @var Requirements
	 */
	private static $backend = null;

	public static function backend() {
		if(!self::$backend) {
			self::$backend = new Requirements_Backend();
		}
		return self::$backend;
	}

	/**
	 * Setter method for changing the Requirements backend
	 *
	 * @param Requirements $backend
	 */
	public static function set_backend(Requirements_Backend $backend) {
		self::$backend = $backend;
	}

	/**
	 * Register the given javascript file as required.
	 *
	 * See {@link Requirements_Backend::javascript()} for more info
	 *
	 */
	public static function javascript($file) {
		self::backend()->javascript($file);
	}

	/**
	 * Add the javascript code to the header of the page
	 *
	 * See {@link Requirements_Backend::customScript()} for more info
	 * @param script The script content
	 * @param uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public static function customScript($script, $uniquenessID = null) {
		self::backend()->customScript($script, $uniquenessID);
	}

	/**
	 * Include custom CSS styling to the header of the page.
	 *
	 * See {@link Requirements_Backend::customCSS()}
	 *
	 * @param string $script CSS selectors as a string (without <style> tag enclosing selectors).
	 * @param int $uniquenessID Group CSS by a unique ID as to avoid duplicate custom CSS in header
	 */
	public static function customCSS($script, $uniquenessID = null) {
		self::backend()->customCSS($script, $uniquenessID);
	}

	/**
	 * Add the following custom code to the <head> section of the page.
	 * See {@link Requirements_Backend::insertHeadTags()}
	 *
	 * @param string $html
	 * @param string $uniquenessID
	 */
	public static function insertHeadTags($html, $uniquenessID = null) {
		self::backend()->insertHeadTags($html, $uniquenessID);
	}

	/**
	 * Load the given javascript template with the page.
	 * See {@link Requirements_Backend::javascriptTemplate()}
	 *
	 * @param file The template file to load.
	 * @param vars The array of variables to load.  These variables are loaded via string search & replace.
	 */
	public static function javascriptTemplate($file, $vars, $uniquenessID = null) {
		self::backend()->javascriptTemplate($file, $vars, $uniquenessID);
	}

	/**
	 * Register the given stylesheet file as required.
	 * See {@link Requirements_Backend::css()}
	 *
	 * @param $file String Filenames should be relative to the base, eg, 'framework/javascript/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector")
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public static function css($file, $media = null) {
		self::backend()->css($file, $media);
	}

	/**
	 * Registers the given themeable stylesheet as required.
	 *
	 * A CSS file in the current theme path name "themename/css/$name.css" is
	 * first searched for, and it that doesn't exist and the module parameter is
	 * set then a CSS file with that name in the module is used.
	 *
	 * NOTE: This API is experimental and may change in the future.
	 *
	 * @param string $name The name of the file - e.g. "/css/File.css" would have
	 *        the name "File".
	 * @param string $module The module to fall back to if the css file does not
	 *        exist in the current theme.
	 * @param string $media The CSS media attribute.
	 */
	public static function themedCSS($name, $module = null, $media = null) {
		return self::backend()->themedCSS($name, $module, $media);
	}

	/**
	 * Clear either a single or all requirements.
	 * Caution: Clearing single rules works only with customCSS and customScript if you specified a {@uniquenessID}.
	 *
	 * See {@link Requirements_Backend::clear()}
	 *
	 * @param $file String
	 */
	public static function clear($fileOrID = null) {
		self::backend()->clear($fileOrID);
	}

	/**
	 * Blocks inclusion of a specific file
	 * See {@link Requirements_Backend::block()}
	 *
	 * @param unknown_type $fileOrID
	 */
	public static function block($fileOrID) {
		self::backend()->block($fileOrID);
	}

	/**
	 * Removes an item from the blocking-list.
	 * See {@link Requirements_Backend::unblock()}
	 *
	 * @param string $fileOrID
	 */
	public static function unblock($fileOrID) {
		self::backend()->unblock($fileOrID);
	}

	/**
	 * Removes all items from the blocking-list.
	 * See {@link Requirements_Backend::unblock_all()}
	 */
	public static function unblock_all() {
		self::backend()->unblock_all();
	}

	/**
	 * Restore requirements cleared by call to Requirements::clear
	 * See {@link Requirements_Backend::restore()}
	 */
	public static function restore() {
		self::backend()->restore();
	}

	/**
	 * Update the given HTML content with the appropriate include tags for the registered
	 * requirements.
	 * See {@link Requirements_Backend::includeInHTML()} for more information.
	 *
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath
	 * through {@link SSViewer}.
	 * @return string HTML content thats augumented with the requirements before the closing <head> tag.
	 */
	public static function includeInHTML($templateFile, $content) {
		return self::backend()->includeInHTML($templateFile, $content);
	}

	public static function include_in_response(SS_HTTPResponse $response) {
		return self::backend()->include_in_response($response);
	}

	/**
	 * Add i18n files from the given javascript directory.
	 *
	 * @param String
	 * @param Boolean
	 * @param Boolean
	 *
	 * See {@link Requirements_Backend::add_i18n_javascript()} for more information.
	 */
	public static function add_i18n_javascript($langDir, $return = false, $langOnly = false) {
		return self::backend()->add_i18n_javascript($langDir, $return, $langOnly);
	}

	/**
	 * Concatenate several css or javascript files into a single dynamically generated file.
	 * See {@link Requirements_Backend::combine_files()} for more info.
	 *
	 * @param string $combinedFileName
	 * @param array $files
	 * @param string $media
	 */
	public static function combine_files($combinedFileName, $files, $media = null) {
		self::backend()->combine_files($combinedFileName, $files, $media);
	}

	/**
	 * Returns all combined files.
	 * See {@link Requirements_Backend::get_combine_files()}
	 *
	 * @return array
	 */
	public static function get_combine_files() {
		return self::backend()->get_combine_files();
	}

	/**
	 * Deletes all dynamically generated combined files from the filesystem.
	 * See {@link Requirements_Backend::delete_combine_files()}
	 *
	 * @param string $combinedFileName If left blank, all combined files are deleted.
	 */
	public static function delete_combined_files($combinedFileName = null) {
		return self::backend()->delete_combined_files($combinedFileName);
	}


	/**
	 * Re-sets the combined files definition. See {@link Requirements_Backend::clear_combined_files()}
	 */
	public static function clear_combined_files() {
		self::backend()->clear_combined_files();
	}

	/**
	 * See {@link combine_files()}.
 	 */
	public static function process_combined_files() {
		return self::backend()->process_combined_files();
	}

	/**
	 * Returns all custom scripts
	 * See {@link Requirements_Backend::get_custom_scripts()}
	 *
	 * @return array
	 */
	public static function get_custom_scripts() {
		return self::backend()->get_custom_scripts();
	}

	/**
	 * Set whether you want to write the JS to the body of the page or
	 * in the head section
	 *
	 * @see Requirements_Backend::set_write_js_to_body()
	 * @param boolean
	 */
	public static function set_write_js_to_body($var) {
		self::backend()->set_write_js_to_body($var);
	}

	/**
	 * Set the javascript to be forced to end of the HTML, or use the default.
	 * Useful if you use inline <script> tags, that don't need the javascripts
	 * included via Requirements::require();
	 *
	 * @param boolean $var If true, force the javascripts to be included at the bottom.
	 */
	public static function set_force_js_to_bottom($var) {
		self::backend()->set_force_js_to_bottom($var);
	}

	public static function debug() {
		return self::backend()->debug();
	}

}

/**
 * @package framework
 * @subpackage view
 */
class Requirements_Backend {

	/**
	 * Do we want requirements to suffix onto the requirement link
	 * tags for caching or is it disabled. Getter / Setter available
	 * through {@link Requirements::set_suffix_requirements()}
	 *
	 * @var bool
	 */
	protected $suffix_requirements = true;

	/**
	 * Enable combining of css/javascript files.
	 *
	 * @var boolean
	 */
	protected $combined_files_enabled = true;

	/**
	 * Paths to all required .js files relative to the webroot.
	 *
	 * @var array $javascript
	 */
	protected $javascript = array();

	/**
	 * Paths to all required .css files relative to the webroot.
	 *
	 * @var array $css
	 */
	protected $css = array();

	/**
	 * All custom javascript code that is inserted
	 * directly at the bottom of the HTML <head> tag.
	 *
	 * @var array $customScript
	 */
	protected $customScript = array();

	/**
	 * All custom CSS rules which are inserted
	 * directly at the bottom of the HTML <head> tag.
	 *
	 * @var array $customCSS
	 */
	protected $customCSS = array();

	/**
	 * All custom HTML markup which is added before
	 * the closing <head> tag, e.g. additional metatags.
	 * This is preferred to entering tags directly into
	 */
	protected $customHeadTags = array();

	/**
	 * Remembers the filepaths of all cleared Requirements
	 * through {@link clear()}.
	 *
	 * @var array $disabled
	 */
	protected $disabled = array();

	/**
	 * The filepaths (relative to webroot) or
	 * uniquenessIDs of any included requirements
	 * which should be blocked when executing {@link inlcudeInHTML()}.
	 * This is useful to e.g. prevent core classes to modifying
	 * Requirements without subclassing the entire functionality.
	 * Use {@link unblock()} or {@link unblock_all()} to revert changes.
	 *
	 * @var array $blocked
	 */
	protected $blocked = array();

	/**
	 * See {@link combine_files()}.
	 *
	 * @var array $combine_files
	 */
	public $combine_files = array();

	/**
	 * Using the JSMin library to minify any
	 * javascript file passed to {@link combine_files()}.
	 *
	 * @var boolean
	 */
	public $combine_js_with_jsmin = true;

	/**
	 * Setting for whether or not a file header should be written when
	 * combining files.
	 *
	 * @var boolean
	 */
	public $write_header_comment = true;

	/**
	 * @var string By default, combined files are stored in assets/_combinedfiles.
	 * Set this by calling Requirements::set_combined_files_folder()
	 */
	protected $combinedFilesFolder = null;

	/**
	 * Put all javascript includes at the bottom of the template
	 * before the closing <body> tag instead of the <head> tag.
	 * This means script downloads won't block other HTTP-requests,
	 * which can be a performance improvement.
	 * Caution: Doesn't work when modifying the DOM from those external
	 * scripts without listening to window.onload/document.ready
	 * (e.g. toplevel document.write() calls).
	 *
	 * @see http://developer.yahoo.com/performance/rules.html#js_bottom
	 *
	 * @var boolean
	 */
	public $write_js_to_body = true;
	
	/**
	 * Force the javascripts to the bottom of the page, even if there's a
	 * <script> tag in the body already
	 *  
	 * @var boolean
	 */
	protected $force_js_to_bottom = false;
	
	public function set_combined_files_enabled($enable) {
		$this->combined_files_enabled = (bool) $enable;
	}

	public function get_combined_files_enabled() {
		return $this->combined_files_enabled;
	}

	/**
	 * @param String $folder
	 */
	public function setCombinedFilesFolder($folder) {
		$this->combinedFilesFolder = $folder;
	}

	/**
	 * @return String Folder relative to the webroot
	 */
	public function getCombinedFilesFolder() {
		return ($this->combinedFilesFolder) ? $this->combinedFilesFolder : ASSETS_DIR . '/_combinedfiles';
	}

	/**
	 * Set whether we want to suffix requirements with the time /
	 * location on to the requirements
	 *
	 * @param bool
	 */
	public function set_suffix_requirements($var) {
		$this->suffix_requirements = $var;
	}

	/**
	 * Return whether we want to suffix requirements
	 *
	 * @return bool
	 */
	public function get_suffix_requirements() {
		return $this->suffix_requirements;
	}

	/**
	 * Set whether you want the files written to the head or the body. It
	 * writes to the body by default which can break some scripts
	 *
	 * @param boolean
	 */
	public function set_write_js_to_body($var) {
		$this->write_js_to_body = $var;
	}
	/**
	 * Forces the javascript to the end of the body, just before the closing body-tag.
	 *
	 * @param boolean
	 */
	public function set_force_js_to_bottom($var) {
		$this->force_js_to_bottom = $var;
	}
	/**
	 * Register the given javascript file as required.
	 * Filenames should be relative to the base, eg, 'framework/javascript/loader.js'
	 */

	public function javascript($file) {
		$this->javascript[$file] = true;
	}

	/**
	 * Returns an array of all included javascript
	 *
	 * @return array
	 */
	public function get_javascript() {
		return array_keys(array_diff_key($this->javascript,$this->blocked));
	}

	/**
	 * Add the javascript code to the header of the page
	 * @todo Make Requirements automatically put this into a separate file :-)
	 * @param script The script content
	 * @param uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public function customScript($script, $uniquenessID = null) {
		if($uniquenessID) $this->customScript[$uniquenessID] = $script;
		else $this->customScript[] = $script;

		$script .= "\n";
	}

	/**
	 * Include custom CSS styling to the header of the page.
	 *
	 * @param string $script CSS selectors as a string (without <style> tag enclosing selectors).
	 * @param int $uniquenessID Group CSS by a unique ID as to avoid duplicate custom CSS in header
	 */
	public function customCSS($script, $uniquenessID = null) {
		if($uniquenessID) $this->customCSS[$uniquenessID] = $script;
		else $this->customCSS[] = $script;
	}

	/**
	 * Add the following custom code to the <head> section of the page.
	 *
	 * @param string $html
	 * @param string $uniquenessID
	 */
	public function insertHeadTags($html, $uniquenessID = null) {
		if($uniquenessID) $this->customHeadTags[$uniquenessID] = $html;
		else $this->customHeadTags[] = $html;
	}

	/**
	 * Load the given javascript template with the page.
	 * @param file The template file to load.
	 * @param vars The array of variables to load.  These variables are loaded via string search & replace.
	 */
	public function javascriptTemplate($file, $vars, $uniquenessID = null) {
		$script = file_get_contents(Director::getAbsFile($file));
		$search = array();
		$replace = array();

		if($vars) foreach($vars as $k => $v) {
			$search[] = '$' . $k;
			$replace[] = str_replace("\\'","'", Convert::raw2js($v));
		}

		$script = str_replace($search, $replace, $script);
		$this->customScript($script, $uniquenessID);
	}

	/**
	 * Register the given stylesheet file as required.
	 *
	 * @param $file String Filenames should be relative to the base, eg, 'framework/javascript/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector")
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public function css($file, $media = null) {
		$this->css[$file] = array(
			"media" => $media
		);
	}

	public function get_css() {
		return array_diff_key($this->css, $this->blocked);
	}

	/**
	 * Needed to actively prevent the inclusion of a file,
	 * e.g. when using your own jQuery version.
	 * Blocking should only be used as an exception, because
	 * it is hard to trace back. You can just block items with an
	 * ID, so make sure you add an unique identifier to customCSS() and customScript().
	 *
	 * @param string $fileOrID
	 */
	public function block($fileOrID) {
		$this->blocked[$fileOrID] = $fileOrID;
	}

	/**
	 * Clear either a single or all requirements.
	 * Caution: Clearing single rules works only with customCSS and customScript if you specified a {@uniquenessID}.
	 *
	 * @param $file String
	 */
	public function clear($fileOrID = null) {
		if($fileOrID) {
			foreach(array('javascript','css', 'customScript', 'customCSS', 'customHeadTags') as $type) {
				if(isset($this->{$type}[$fileOrID])) {
					$this->disabled[$type][$fileOrID] = $this->{$type}[$fileOrID];
					unset($this->{$type}[$fileOrID]);
				}
			}
		} else {
			$this->disabled['javascript'] = $this->javascript;
			$this->disabled['css'] = $this->css;
			$this->disabled['customScript'] = $this->customScript;
			$this->disabled['customCSS'] = $this->customCSS;
			$this->disabled['customHeadTags'] = $this->customHeadTags;

			$this->javascript = array();
			$this->css = array();
			$this->customScript = array();
			$this->customCSS = array();
			$this->customHeadTags = array();
		}
	}

	/**
	 * Removes an item from the blocking-list.
	 * CAUTION: Does not "re-add" any previously blocked elements.
	 * @param string $fileOrID
	 */
	public function unblock($fileOrID) {
		if(isset($this->blocked[$fileOrID])) unset($this->blocked[$fileOrID]);
	}
	/**
	 * Removes all items from the blocking-list.
	 */
	public function unblock_all() {
		$this->blocked = array();
	}

	/**
	 * Restore requirements cleared by call to Requirements::clear
	 */
	public function restore() {
		$this->javascript = $this->disabled['javascript'];
		$this->css = $this->disabled['css'];
		$this->customScript = $this->disabled['customScript'];
		$this->customCSS = $this->disabled['customCSS'];
		$this->customHeadTags = $this->disabled['customHeadTags'];
	}

	/**
	 * Update the given HTML content with the appropriate include tags for the registered
	 * requirements. Needs to receive a valid HTML/XHTML template in the $content parameter,
	 * including a <head> tag. The requirements will insert before the closing <head> tag automatically.
	 *
	 * @todo Calculate $prefix properly
	 *
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath
	 *                        through {@link SSViewer}.
	 * @return string HTML content thats augumented with the requirements before the closing <head> tag.
	 */
	public function includeInHTML($templateFile, $content) {
		if(
			(strpos($content, '</head>') !== false || strpos($content, '</head ') !== false)
			&& ($this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags)
		) {
			$requirements = '';
			$jsRequirements = '';

			// Combine files - updates $this->javascript and $this->css
			$this->process_combined_files();

			foreach(array_diff_key($this->javascript,$this->blocked) as $file => $dummy) {
				$path = Convert::raw2xml($this->path_for_file($file));
				if($path) {
					$jsRequirements .= "<script type=\"text/javascript\" src=\"$path\"></script>\n";
				}
			}

			// add all inline javascript *after* including external files which
			// they might rely on
			if($this->customScript) {
				foreach(array_diff_key($this->customScript,$this->blocked) as $script) {
					$jsRequirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
					$jsRequirements .= "$script\n";
					$jsRequirements .= "\n//]]>\n</script>\n";
				}
			}

			foreach(array_diff_key($this->css,$this->blocked) as $file => $params) {
				$path = Convert::raw2xml($this->path_for_file($file));
				if($path) {
					$media = (isset($params['media']) && !empty($params['media']))
						? " media=\"{$params['media']}\"" : "";
					$requirements .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"$path\" />\n";
				}
			}

			foreach(array_diff_key($this->customCSS, $this->blocked) as $css) {
				$requirements .= "<style type=\"text/css\">\n$css\n</style>\n";
			}

			foreach(array_diff_key($this->customHeadTags,$this->blocked) as $customHeadTag) {
				$requirements .= "$customHeadTag\n";
			}

			if ($this->force_js_to_bottom) {
				// Remove all newlines from code to preserve layout
				$jsRequirements = preg_replace('/>\n*/', '>', $jsRequirements);

				// We put script tags into the body, for performance.
				// We forcefully put it at the bottom instead of before
				// the first script-tag occurence
				$content = preg_replace("/(<\/body[^>]*>)/i", $jsRequirements . "\\1", $content);
				
				// Put CSS at the bottom of the head
				$content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);				
			} elseif($this->write_js_to_body) {
				// Remove all newlines from code to preserve layout
				$jsRequirements = preg_replace('/>\n*/', '>', $jsRequirements);

				// We put script tags into the body, for performance.
				// If your template already has script tags in the body, then we put our script
				// tags just before those. Otherwise, we put it at the bottom.
				$p2 = stripos($content, '<body');
				$p1 = stripos($content, '<script', $p2);

				if($p1 !== false) {
					$content = substr($content,0,$p1) . $jsRequirements . substr($content,$p1);
				} else {
					$content = preg_replace("/(<\/body[^>]*>)/i", $jsRequirements . "\\1", $content);
				}

				// Put CSS at the bottom of the head
				$content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);
			} else {
				$content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);
				$content = preg_replace("/(<\/head>)/i", $jsRequirements . "\\1", $content);
			}
		}

		return $content;
	}

	/**
	 * Attach requirements inclusion to X-Include-JS and X-Include-CSS headers on the HTTP response
	 */
	public function include_in_response(SS_HTTPResponse $response) {
		$this->process_combined_files();
		$jsRequirements = array();
		$cssRequirements = array();

		foreach(array_diff_key($this->javascript, $this->blocked) as $file => $dummy) {
			$path = $this->path_for_file($file);
			if($path) {
				$jsRequirements[] = str_replace(',', '%2C', $path);
			}
		}

		$response->addHeader('X-Include-JS', implode(',', $jsRequirements));

		foreach(array_diff_key($this->css,$this->blocked) as $file => $params) {
			$path = $this->path_for_file($file);
			if($path) {
				$path = str_replace(',', '%2C', $path);
				$cssRequirements[] = isset($params['media']) ? "$path:##:$params[media]" : $path;
			}
		}

		$response->addHeader('X-Include-CSS', implode(',', $cssRequirements));
	}

	/**
	 * Add i18n files from the given javascript directory.  SilverStripe expects that the given directory
	 * will contain a number of java script files named by language: en_US.js, de_DE.js, etc.
	 *
	 * @param String The javascript lang directory, relative to the site root, e.g., 'framework/javascript/lang'
	 * @param Boolean Return all relative file paths rather than including them in requirements
	 * @param Boolean Only include language files, not the base libraries
	 */
	public function add_i18n_javascript($langDir, $return = false, $langOnly = false) {
		$files = array();
		$base = Director::baseFolder() . '/';
		if(i18n::config()->js_i18n) {
			// Include i18n.js even if no languages are found.  The fact that
			// add_i18n_javascript() was called indicates that the methods in
			// here are needed.
			if(!$langOnly) $files[] = FRAMEWORK_DIR . '/javascript/i18n.js';

			if(substr($langDir,-1) != '/') $langDir .= '/';

			$candidates = array(
				'en.js',
				'en_US.js',
				i18n::get_lang_from_locale(i18n::default_locale()) . '.js',
				i18n::default_locale() . '.js',
				i18n::get_lang_from_locale(i18n::get_locale()) . '.js',
				i18n::get_locale() . '.js',
			);
			foreach($candidates as $candidate) {
				if(file_exists($base . DIRECTORY_SEPARATOR . $langDir . $candidate)) {
					$files[] = $langDir . $candidate;
				}
			}
		} else {
			// Stub i18n implementation for when i18n is disabled.
			if(!$langOnly) $files[] = FRAMEWORK_DIR . '/javascript/i18nx.js';
		}

		if($return) {
			return $files;
		} else {
			foreach($files as $file) $this->javascript($file);
		}
	}

	/**
	 * Finds the path for specified file.
	 *
	 * @param string $fileOrUrl
	 * @return string|boolean
	 */
	protected function path_for_file($fileOrUrl) {
		if(preg_match('{^//|http[s]?}', $fileOrUrl)) {
			return $fileOrUrl;
		} elseif(Director::fileExists($fileOrUrl)) {
			$filePath = preg_replace('/\?.*/', '', Director::baseFolder() . '/' . $fileOrUrl);
			$prefix = Director::baseURL();
			$mtimesuffix = "";
			$suffix = '';
			if($this->suffix_requirements) {
				$mtimesuffix = "?m=" . filemtime($filePath);
				$suffix = '&';
			}
			if(strpos($fileOrUrl, '?') !== false) {
				if (strlen($suffix) == 0) {
					$suffix = '?';
				}
				$suffix .= substr($fileOrUrl, strpos($fileOrUrl, '?')+1);
				$fileOrUrl = substr($fileOrUrl, 0, strpos($fileOrUrl, '?'));
			} else {
				$suffix = '';
			}
			return "{$prefix}{$fileOrUrl}{$mtimesuffix}{$suffix}";
		} else {
			return false;
		}
	}

	/**
	 * Concatenate several css or javascript files into a single dynamically generated
	 * file (stored in {@link Director::baseFolder()}). This increases performance
	 * by fewer HTTP requests.
	 *
	 * The combined file is regenerated
	 * based on every file modification time. Optionally a rebuild can be triggered
	 * by appending ?flush=1 to the URL.
	 * If all files to be combined are javascript, we use the external JSMin library
	 * to minify the javascript. This can be controlled by {@link $combine_js_with_jsmin}.
	 *
	 * All combined files will have a comment on the start of each concatenated file
	 * denoting their original position. For easier debugging, we recommend to only
	 * minify javascript if not in development mode ({@link Director::isDev()}).
	 *
	 * CAUTION: You're responsible for ensuring that the load order for combined files
	 * is retained - otherwise combining javascript files can lead to functional errors
	 * in the javascript logic, and combining css can lead to wrong styling inheritance.
	 * Depending on the javascript logic, you also have to ensure that files are not included
	 * in more than one combine_files() call.
	 * Best practice is to include every javascript file in exactly *one* combine_files()
	 * directive to avoid the issues mentioned above - this is enforced by this function.
	 *
	 * CAUTION: Combining CSS Files discards any "media" information.
	 *
	 * Example for combined JavaScript:
	 * <code>
	 * Requirements::combine_files(
	 *  'foobar.js',
	 *  array(
	 * 		'mysite/javascript/foo.js',
	 * 		'mysite/javascript/bar.js',
	 * 	)
	 * );
	 * </code>
	 *
	 * Example for combined CSS:
	 * <code>
	 * Requirements::combine_files(
	 *  'foobar.css',
	 * 	array(
	 * 		'mysite/javascript/foo.css',
	 * 		'mysite/javascript/bar.css',
	 * 	)
	 * );
	 * </code>
	 *
	 * @see http://code.google.com/p/jsmin-php/
	 *
	 * @todo Should we enforce unique inclusion of files, or leave it to the developer? Can auto-detection cause
	 *       breaks?
	 *
	 * @param string $combinedFileName Filename of the combined file (will be stored in {@link Director::baseFolder()}
	 *                                 by default)
	 * @param array $files Array of filenames relative to the webroot
	 * @param string $media Comma-separated list of media-types (e.g. "screen,projector").
	 */
	public function combine_files($combinedFileName, $files, $media = null) {
		// duplicate check
		foreach($this->combine_files as $_combinedFileName => $_files) {
			$duplicates = array_intersect($_files, $files);
			if($duplicates && $combinedFileName != $_combinedFileName) {
				user_error("Requirements_Backend::combine_files(): Already included files " . implode(',', $duplicates)
					. " in combined file '{$_combinedFileName}'", E_USER_NOTICE);
				return false;
			}
		}
		foreach($files as $index=>$file) {
			if(is_array($file)) {
				// Either associative array path=>path type=>type or numeric 0=>path 1=>type
				// Otherwise, assume path is the first item
				if (isset($file['type']) && in_array($file['type'], array('css', 'javascript', 'js'))) {
					switch ($file['type']) {
						case 'css':
							$this->css($file['path'], $media);
							break;
						default:
							$this->javascript($file['path']);
							break;
					}
					$files[$index] = $file['path'];
				} elseif (isset($file[1]) && in_array($file[1], array('css', 'javascript', 'js'))) {
					switch ($file[1]) {
						case 'css':
							$this->css($file[0], $media);
							break;
						default:
							$this->javascript($file[0]);
							break;
					}
					$files[$index] = $file[0];
				} else {
					$file = array_shift($file);
				}
			}
			if (!is_array($file)) {
				if(substr($file, -2) == 'js') {
					$this->javascript($file);
				} elseif(substr($file, -3) == 'css') {
					$this->css($file, $media);
				} else {
					user_error("Requirements_Backend::combine_files(): Couldn't guess file type for file '$file', "
						. "please specify by passing using an array instead.", E_USER_NOTICE);
				}
			}
		}
		$this->combine_files[$combinedFileName] = $files;
	}

		/**
	 * Returns all combined files.
	 * @return array
	 */
	public function get_combine_files() {
		return $this->combine_files;
	}

	/**
	 * Deletes all dynamically generated combined files from the filesystem.
	 *
	 * @param string $combinedFileName If left blank, all combined files are deleted.
	 */
	public function delete_combined_files($combinedFileName = null) {
		$combinedFiles = ($combinedFileName) ? array($combinedFileName => null) : $this->combine_files;
		$combinedFolder = ($this->getCombinedFilesFolder()) ?
			(Director::baseFolder() . '/' . $this->combinedFilesFolder) : Director::baseFolder();
		foreach($combinedFiles as $combinedFile => $sourceItems) {
			$filePath = $combinedFolder . '/' . $combinedFile;
			if(file_exists($filePath)) {
				unlink($filePath);
			}
		}
	}

	public function clear_combined_files() {
		$this->combine_files = array();
	}

	/**
	 * See {@link combine_files()}
	 *
	 */
	public function process_combined_files() {
		// The class_exists call prevents us from loading SapphireTest.php (slow) just to know that
		// SapphireTest isn't running :-)
		if(class_exists('SapphireTest', false)) $runningTest = SapphireTest::is_running_test();
		else $runningTest = false;

		if((Director::isDev() && !$runningTest && !isset($_REQUEST['combine'])) || !$this->combined_files_enabled) {
			return;
		}

		// Make a map of files that could be potentially combined
		$combinerCheck = array();
		foreach($this->combine_files as $combinedFile => $sourceItems) {
			foreach($sourceItems as $sourceItem) {
				if(isset($combinerCheck[$sourceItem]) && $combinerCheck[$sourceItem] != $combinedFile){
					user_error("Requirements_Backend::process_combined_files - file '$sourceItem' appears in two " .
						"combined files:" .	" '{$combinerCheck[$sourceItem]}' and '$combinedFile'", E_USER_WARNING);
				}
				$combinerCheck[$sourceItem] = $combinedFile;

			}
		}

		// Work out the relative URL for the combined files from the base folder
		$combinedFilesFolder = ($this->getCombinedFilesFolder()) ? ($this->getCombinedFilesFolder() . '/') : '';

		// Figure out which ones apply to this pageview
		$combinedFiles = array();
		$newJSRequirements = array();
		$newCSSRequirements = array();
		foreach($this->javascript as $file => $dummy) {
			if(isset($combinerCheck[$file])) {
				$newJSRequirements[$combinedFilesFolder . $combinerCheck[$file]] = true;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newJSRequirements[$file] = true;
			}
		}

		foreach($this->css as $file => $params) {
			if(isset($combinerCheck[$file])) {
				// Inherit the parameters from the last file in the combine set.
				$newCSSRequirements[$combinedFilesFolder . $combinerCheck[$file]] = $params;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newCSSRequirements[$file] = $params;
			}
		}

		// Process the combined files
		$base = Director::baseFolder() . '/';
		foreach(array_diff_key($combinedFiles, $this->blocked) as $combinedFile => $dummy) {
			$fileList = $this->combine_files[$combinedFile];
			$combinedFilePath = $base . $combinedFilesFolder . '/' . $combinedFile;


			// Make the folder if necessary
			if(!file_exists(dirname($combinedFilePath))) {
				Filesystem::makeFolder(dirname($combinedFilePath));
			}

			// If the file isn't writeable, don't even bother trying to make the combined file and return (falls back
			//  to uncombined).  Complex test because is_writable fails if the file doesn't exist yet.
			if((file_exists($combinedFilePath) && !is_writable($combinedFilePath))
				|| (!file_exists($combinedFilePath) && !is_writable(dirname($combinedFilePath)))
			) {
				user_error("Requirements_Backend::process_combined_files(): Couldn't create '$combinedFilePath'",
					E_USER_WARNING);
				return false;
			}

			// Determine if we need to build the combined include
			if(file_exists($combinedFilePath) && !isset($_GET['flush'])) {
				// file exists, check modification date of every contained file
				$srcLastMod = 0;
				foreach($fileList as $file) {
					if(file_exists($base . $file)) {
						$srcLastMod = max(filemtime($base . $file), $srcLastMod);
					}
				}
				$refresh = $srcLastMod > filemtime($combinedFilePath);
			} else {
				// file doesn't exist, or refresh was explicitly required
				$refresh = true;
			}

			if(!$refresh) continue;

			$combinedData = "";
			foreach(array_diff($fileList, $this->blocked) as $file) {
				$fileContent = file_get_contents($base . $file);
				$fileContent = $this->minifyFile($file, $fileContent);

				if ($this->write_header_comment) {
					// write a header comment for each file for easier identification and debugging
					// also the semicolon between each file is required for jQuery to be combinable properly
					$combinedData .= "/****** FILE: $file *****/\n";
				}

				$combinedData .= $fileContent . "\n";
			}

			$successfulWrite = false;
			$fh = fopen($combinedFilePath, 'wb');
			if($fh) {
				if(fwrite($fh, $combinedData) == strlen($combinedData)) $successfulWrite = true;
				fclose($fh);
				unset($fh);
			}

			// Unsuccessful write - just include the regular JS files, rather than the combined one
			if(!$successfulWrite) {
				user_error("Requirements_Backend::process_combined_files(): Couldn't create '$combinedFilePath'",
					E_USER_WARNING);
				continue;
			}
		}

		// @todo Alters the original information, which means you can't call this
		// method repeatedly - it will behave different on the second call!
		$this->javascript = $newJSRequirements;
		$this->css = $newCSSRequirements;
	}

	protected function minifyFile($filename, $content) {
		// if we have a javascript file and jsmin is enabled, minify the content
		$isJS = stripos($filename, '.js');
		if($isJS && $this->combine_js_with_jsmin) {
			require_once('thirdparty/jsmin/jsmin.php');

			increase_time_limit_to();
			$content = JSMin::minify($content);
		}
		$content .= ($isJS ? ';' : '') . "\n";
		return $content;
	}

	public function get_custom_scripts() {
		$requirements = "";

		if($this->customScript) {
			foreach($this->customScript as $script) {
				$requirements .= "$script\n";
			}
		}

		return $requirements;
	}

	/**
	 * @see Requirements::themedCSS()
	 */
	public function themedCSS($name, $module = null, $media = null) {
		$theme = SSViewer::get_theme_folder();
		$project = project();
		$absbase = BASE_PATH . DIRECTORY_SEPARATOR;
		$abstheme = $absbase . $theme;
		$absproject = $absbase . $project;
		$css = "/css/$name.css";
		
		if(file_exists($absproject . $css)) {
			$this->css($project . $css, $media);
		} elseif($module && file_exists($abstheme . '_' . $module.$css)) {
			$this->css($theme . '_' . $module . $css, $media);
		} elseif(file_exists($abstheme . $css)) {
			$this->css($theme . $css, $media);
		} elseif($module) {
			$this->css($module . $css, $media);
		}
	}

	public function debug() {
		Debug::show($this->javascript);
		Debug::show($this->css);
		Debug::show($this->customCSS);
		Debug::show($this->customScript);
		Debug::show($this->customHeadTags);
		Debug::show($this->combine_files);
	}

}
