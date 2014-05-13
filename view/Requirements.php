<?php
/**
 * Front-end for requirements handler for JavaScript and CSS files.
 * @package framework
 * @subpackage view
 */
class Requirements implements Flushable, TemplateGlobalProvider {

	/**
	 * Using the JSMin library to minify any
	 * javascript file passed to {@link combine_files()}.
	 * @config
	 * @var boolean
	 */
	private static $combine_js_with_jsmin = true;

	/**
	 * Enable combining of css/javascript files
	 * @config
	 * @var boolean
	 */
	private static $combined_files_enabled = true;

	/**
	 * The folder that combined files are stored in
	 * @config
	 * @var string
	 */
	private static $combined_files_folder = '$AssetsDir/_combinedfiles';

	/**
	 * Force the javascripts to the bottom of the page, even if there's a
	 * <script> tag in the body already
	 * @config
	 * @var boolean
	 */
	private static $force_js_to_bottom = false;

	/**
	 * Enable adding query string suffix to requirements for caching
	 * @config
	 * @var bool
	 */
	private static $suffix_requirements = true;

	/**
	 * Setting for whether or not a file header should be written when
	 * combining files.
	 * @config
	 * @var boolean
	 */
	private static $write_header_comment = true;

	/**
	 * Put all javascript includes at the bottom of the template
	 * before the closing <body> tag instead of the <head> tag.
	 * This means script downloads won't block other HTTP-requests,
	 * which can be a performance improvement.
	 * Caution: Doesn't work when modifying the DOM from those external
	 * scripts without listening to window.onload/document.ready
	 * (e.g. toplevel document.write() calls).
	 * @see http://developer.yahoo.com/performance/rules.html#js_bottom
	 * @config
	 * @var boolean
	 */
	private static $write_js_to_body = true;

	/**
	 * Triggered early in the request when someone requests a flush.
	 */
	public static function flush() {
		self::delete_all_combined_files();
	}

	/**
	 * @return array
	 */
	public static function get_template_global_variables() {
		return array(
			'javascript',
			'css',
			'customScript' => 'custom_script',
			'customCSS' => 'custom_css',
			'themedCSS' => 'themed_css',
			'insertHeadTags' => 'insert_head_tags',
			'javascriptTemplate' => 'javascript_template',
			'clear',
			'block',
			'unblock'
		);
	}

	/**
	 * Returns an instance of Requirements_Backend
	 * @return Requirements_Backend
	 */
	public static function backend() {
		return Injector::inst()->get('Requirements_Backend');
	}

	/**
	 * Requirements_Backend should now be set using the Injector api, e.g:
	 * Injector.Requirements_Backend: MyRequirementsHandler
	 * {@link Injector::registerService()} can be used to manually set
	 * an instance
	 * @deprecated 4.0 Use Config system to set backend instead
	 * @param Requirements $backend
	 */
	public static function set_backend(Requirements_Backend $backend) {
		Deprecation::notice('4.0', 'Use Config system to set backend instead');
		Injector::inst()->registerService($backend, 'Requirements_Backend');
	}

	/**
	 * Register the given javascript file as required.
	 * See {@link RequirementsHandler::javascript()} for more info
	 * @param string $file
	 */
	public static function javascript($file) {
		self::backend()->javascript($file);
	}

	/**
	 * Register the given stylesheet file as required.
	 * See {@link RequirementsHandler::css()}
	 * @param string $file Filenames should be relative to the base, eg, 'framework/javascript/tree/tree.css'
	 * @param string $media Comma-separated list of media-types (e.g. "screen,projector")
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public static function css($file, $media = null) {
		self::backend()->css($file, $media);
	}

	/**
	 * Add the javascript code to the header of the page
	 * See {@link RequirementsHandler::customScript()} for more info
	 * @param $script The script content
	 * @param $uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public static function custom_script($script, $uniquenessID = null) {
		self::backend()->customScript($script, $uniquenessID);
	}

	/**
	 * Include custom CSS styling to the header of the page.
	 * See {@link RequirementsHandler::customCSS()}
	 * @param string $script CSS selectors as a string (without <style> tag enclosing selectors).
	 * @param int $uniquenessID Group CSS by a unique ID as to avoid duplicate custom CSS in header
	 */
	public static function custom_css($script, $uniquenessID = null) {
		self::backend()->customCSS($script, $uniquenessID);
	}

	/**
	 * Add the following custom code to the <head> section of the page.
	 * See {@link RequirementsHandler::insertHeadTags()}
	 * @param string $html
	 * @param string $uniquenessID
	 */
	public static function insert_head_tags($html, $uniquenessID = null) {
		self::backend()->insertHeadTags($html, $uniquenessID);
	}

	/**
	 * Load the given javascript template with the page.
	 * See {@link RequirementsHandler::javascriptTemplate()}
	 * @param $file The template file to load.
	 * @param $vars The array of variables to load. These variables are loaded via string search & replace.
	 */
	public static function javascript_template($file, $vars, $uniquenessID = null) {
		self::backend()->javascriptTemplate($file, $vars, $uniquenessID);
	}

	/**
	 * Registers the given themeable stylesheet as required.
	 * A CSS file in the current theme path name "themename/css/$name.css" is
	 * first searched for, and it that doesn't exist and the module parameter is
	 * set then a CSS file with that name in the module is used.
	 * @param string $name The name of the file - e.g. "/css/File.css" would have the name "File".
	 * @param string $module The module to fall back to if the css file does not exist in the current theme.
	 * @param string $media The CSS media attribute.
	 */
	public static function themed_css($name, $module = null, $media = null) {
		self::backend()->themedCSS($name, $module, $media);
	}

	/**
	 * Clear either a single or all requirements.
	 * Caution: Clearing single rules works only with customCSS and customScript if you
	 * specified a {@uniquenessID}.
	 * See {@link RequirementsHandler::clear()}
	 * @param $file String
	 */
	public static function clear($fileOrID = null) {
		self::backend()->clear($fileOrID);
	}

	/**
	 * Blocks inclusion of a specific file
	 * See {@link RequirementsHandler::block()}
	 * @param string $fileOrID
	 */
	public static function block($fileOrID) {
		self::backend()->block($fileOrID);
	}

	/**
	 * Removes an item from the blocking-list.
	 * See {@link RequirementsHandler::unblock()}
	 * @param string $fileOrID
	 */
	public static function unblock($fileOrID) {
		self::backend()->unblock($fileOrID);
	}

	/**
	 * Removes all items from the blocking-list.
	 * See {@link RequirementsHandler::unblockAll()}
	 */
	public static function unblock_all() {
		self::backend()->unblockAll();
	}

	/**
	 * Restore requirements cleared by call to Requirements::clear
	 * See {@link RequirementsHandler::restore()}
	 */
	public static function restore() {
		self::backend()->restore();
	}

	/**
	 * Update the given HTML content with the appropriate include tags for the registered
	 * requirements.
	 * See {@link RequirementsHandler::includeInHTML()} for more information.
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath
	 * through {@link SSViewer}.
	 * @return string HTML content thats augumented with the requirements before the closing <head> tag.
	 */
	public static function include_in_html($templateFile, $content) {
		return self::backend()->includeInHTML($templateFile, $content);
	}

	/**
	 * Attach requirements inclusion to X-Include-JS and X-Include-CSS headers on the HTTP response.
	 * @param SS_HTTPResponse $response
	 */
	public static function include_in_response(SS_HTTPResponse $response) {
		self::backend()->includeInResponse($response);
	}

	/**
	 * Add i18n files from the given javascript directory.
	 * See {@link RequirementsHandler::addI18nJavaScript()} for more information.
	 * @param string
	 * @param boolean
	 * @param boolean
	 * @return array|null
	 */
	public static function add_i18n_javascript($langDir, $return = false, $langOnly = false) {
		return self::backend()->addI18nJavaScript($langDir, $return, $langOnly);
	}

	/**
	 * Concatenate several css or javascript files into a single dynamically generated file.
	 * See {@link RequirementsHandler::combineFiles()} for more info.
	 * @param string $combinedFileName
	 * @param array $files
	 * @param string $media
	 */
	public static function combine_files($combinedFileName, $files, $media = null) {
		self::backend()->combineFiles($combinedFileName, $files, $media);
	}

	/**
	 * Returns all combined files.
	 * See {@link RequirementsHandler::getCombineFiles()}
	 * @return array
	 */
	public static function get_combine_files() {
		return self::backend()->getCombineFiles();
	}

	/**
	 * Deletes all dynamically generated combined files from the filesystem.
	 * See {@link RequirementsHandler::deleteCombineFiles()}
	 * @param string $combinedFileName If left blank, all combined files are deleted.
	 */
	public static function delete_combined_files($combinedFileName = null) {
		self::backend()->deleteCombinedFiles($combinedFileName);
	}

	/**
	 * Deletes all generated combined files in the configured combined files directory,
	 * but doesn't delete the directory itself.
	 */
	public static function delete_all_combined_files() {
		return self::backend()->deleteAllCombinedFiles();
	}

	/**
	 * Re-sets the combined files definition.
	 * See {@link RequirementsHandler::clearCombinedFiles()}
	 */
	public static function clear_combined_files() {
		self::backend()->clearCombinedFiles();
	}

	/**
	 * Trigger processing of combined files
	 * See {@link Requirements::combine_files()}.
	 */
	public static function process_combined_files() {
		return self::backend()->processCombinedFiles();
	}

	/**
	 * Returns all custom scripts
	 * See {@link RequirementsHandler::getCustomScripts()}
	 * @return array
	 */
	public static function get_custom_scripts() {
		return self::backend()->getCustomScripts();
	}

	/**
	 * @deprecated 4.0 Use Requirements::custom_script() instead
	 * @param $script The script content
	 * @param $uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public static function customScript($script, $uniquenessID = null) {
		Deprecation::notice('4.0', 'Use Requirements::custom_script() instead');
		self::custom_script($script, $uniquenessID);
	}

	/**
	 * @deprecated 4.0 Use Requirements::custom_css() instead
	 * @param string $script CSS selectors as a string (without <style> tag enclosing selectors).
	 * @param int $uniquenessID Group CSS by a unique ID as to avoid duplicate custom CSS in header
	 */
	public static function customCSS($script, $uniquenessID = null) {
		Deprecation::notice('4.0', 'Use Requirements::custom_css() instead');
		self::custom_css($script, $uniquenessID);
	}

	/**
	 * @deprecated 4.0 Use Requirements::insert_head_tags() instead
	 * @param string $html
	 * @param string $uniquenessID
	 */
	public static function insertHeadTags($html, $uniquenessID = null) {
		Deprecation::notice('4.0', 'Use Requirements::insert_head_tags() instead');
		self::insert_head_tags($html, $uniquenessID);
	}

	/**
	 * @deprecated 4.0 Use Requirements::javascript_template() instead
	 * @param file The template file to load.
	 * @param vars The array of variables to load.  These variables are loaded via string search & replace.
	 */
	public static function javascriptTemplate($file, $vars, $uniquenessID = null) {
		Deprecation::notice('4.0', 'Use Requirements::javascript_template() instead');
		self::javascript_template($file, $vars, $uniquenessID);
	}

	/**
	 * @deprecated 4.0 Use Requirements::themed_css() instead
	 * @param string $name The name of the file - e.g. "/css/File.css" would have the name "File".
	 * @param string $module The module to fall back to if the css file does not exist in the current theme.
	 * @param string $media The CSS media attribute.
	 */
	public static function themedCSS($name, $module = null, $media = null) {
		Deprecation::notice('4.0', 'Use Requirements::themed_css() instead');
		self::themed_css($name, $module, $media);
	}

	/**
	 * @deprecated 4.0 Use Requirements::include_in_html() instead
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath
	 * through {@link SSViewer}.
	 * @return string HTML content thats augumented with the requirements before the closing <head> tag.
	 */
	public static function includeInHTML($templateFile, $content) {
		Deprecation::notice('4.0', 'Use Requirements::include_in_html() instead');
		return self::include_in_html($templateFile, $content);
	}

	/**
	 * Enable combining of css/javascript files.
	 * @deprecated 4.0 Use the "Requirements.combined_files_enabled" config setting instead
	 * @param boolean $enable
	 */
	public static function set_combined_files_enabled($enable) {
		Deprecation::notice('4.0', 'Use the "Requirements.combined_files_enabled" config setting instead');
		Config::inst()->update('Requirements', 'combined_files_enabled', (bool) $enable);
	}

	/**
	 * Checks whether combining of css/javascript files is enabled.
	 * @deprecated 4.0 Use the "Requirements.combined_files_enabled" config setting instead
	 * @return boolean
	 */
	public static function get_combined_files_enabled() {
		Deprecation::notice('4.0', 'Use the "Requirements.combined_files_enabled" config setting instead');
		return Config::inst()->get('Requirements', 'combined_files_enabled');
	}

	/**
	 * Set the relative folder e.g. "assets" for where to store combined files
	 * @deprecated 4.0 Use the "Requirements.combined_files_folder" config setting instead
	 * @param string $folder Path to folder
	 */
	public static function set_combined_files_folder($folder) {
		Deprecation::notice('4.0', 'Use the "Requirements.combined_files_folder" config setting instead');
		Config::inst()->update('Requirements', 'combined_files_folder', $folder);
	}

	/**
	 * Set whether we want to suffix requirements with the time /
	 * location on to the requirements
	 * @deprecated 4.0 Use the "Requirements.suffix_requirements" config setting instead
	 * @param bool
	 */
	public static function set_suffix_requirements($var) {
		Deprecation::notice('4.0', 'Use the "Requirements.suffix_requirements" config setting instead');
		Config::inst()->update('Requirements', 'suffix_requirements', (bool) $var);
	}

	/**
	 * Return whether we want to suffix requirements
	 * @deprecated 4.0 Use the "Requirements.suffix_requirements" config setting instead
	 * @return bool
	 */
	public static function get_suffix_requirements() {
		Deprecation::notice('4.0', 'Use the "Requirements.suffix_requirements" config setting instead');
		return Config::inst()->get('Requirements', 'suffix_requirements');
	}

	/**
	 * Set whether you want to write the JS to the body of the page or
	 * in the head section
	 * @deprecated 4.0 Use the "Requirements.write_js_to_body" config setting instead
	 * @param boolean
	 */
	public static function set_write_js_to_body($var) {
		Deprecation::notice('4.0', 'Use the "Requirements.write_js_to_body" config setting instead');
		Config::inst()->update('Requirements', 'write_js_to_body', (bool) $var);
	}

	/**
	 * Set the javascript to be forced to end of the HTML, or use the default.
	 * Useful if you use inline <script> tags, that don't need the javascripts
	 * included via Requirements::require();
	 * @deprecated 4.0 Use the "Requirements.force_js_to_bottom" config setting instead
	 * @param boolean
	 */
	public static function set_force_js_to_bottom($var) {
		Deprecation::notice('4.0', 'Use the "Requirements.force_js_to_bottom" config setting instead');
		Config::inst()->update('Requirements', 'force_js_to_bottom', (bool) $var);
	}

	/**
	 * Show a list of all current requirements
	 * @return mixed
	 */
	public static function debug() {
		return self::backend()->debug();
	}

}
