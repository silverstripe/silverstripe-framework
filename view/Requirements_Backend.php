<?php

interface Requirements_Backend {

	/**
	 * Register the given javascript file as required.
	 * @param string $file
	 */
	public function javascript($file);

	/**
	 * Add the javascript code to the document
	 * @param string $script The script content (without <script> tags).
	 * @param int|string $uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public function customScript($script, $uniquenessID = null);

	/**
	 * Add custom CSS to the <head> section of the document.
	 * @param string $script CSS as a string (without <style> tags).
	 * @param int|string $uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public function customCSS($script, $uniquenessID = null);

	/**
	 * Add the following custom code to the <head> section of the document.
	 * @param string $html The HTML to be inserted.
	 * @param int|string $uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public function insertHeadTags($html, $uniquenessID = null);

	/**
	 * Load the given javascript template with the page.
	 * @param string $file The template file to load.
	 * @param array $vars The array of variables to load. These variables are loaded via string search & replace.
	 * @param int|string $uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public function javascriptTemplate($file, $vars, $uniquenessID = null);

	/**
	 * Register the given stylesheet file as required.
	 * @param string $file The CSS file to load.
	 * @param string $media Comma-separated list of media-types (e.g. "screen,projector").
	 */
	public function css($file, $media = null);

	/**
	 * Registers the given themeable stylesheet as required.
	 * @param string $name The name of the file - e.g. "/css/File.css" would have the name "File".
	 * @param string $module The module to fall back to if the css file does not exist in the current theme.
	 * @param string $media Comma-separated list of media-types (e.g. "screen,projector").
	 */
	public function themedCSS($name, $module = null, $media = null);

	/**
	 * Clear either a single or all requirements.
	 * @param string $file If given, only this requirement will be cleared.
	 */
	public function clear($fileOrID = null);

	/**
	 * Prevent inclusion of a previously registered requirement
	 * @param string $fileOrID The filename (or unique ID) of the file to be blocked.
	 */
	public function block($fileOrID);

	/**
	 * Removes an item from the blocking-list.
	 * @param string $fileOrID The filename (or unique ID) of the file to be unblocked.
	 */
	public function unblock($fileOrID);

	/**
	 * Removes all items from the blocking-list.
	 */
	public function unblockAll();

	/**
	 * Restore requirements cleared by call to Requirements::clear
	 */
	public function restore();

	/**
	 * Update the given HTML content with the appropriate include tags for the registered
	 * requirements.
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath
	 *                        through {@link SSViewer}.
	 * @return string HTML content that's augumented with the requirements.
	 */
	public function includeInHTML($templateFile, $content);

	/**
	 * Attach requirements the HTTP response.
	 * @param SS_HTTPResponse $response
	 */
	public function includeInResponse(SS_HTTPResponse $response);

	/**
	 * Add i18n files from the given javascript directory.
	 * @param string $langDir The javascript lang directory
	 * @param boolean $return Return all files rather than including them in requirements.
	 * @param boolean $langOnly Only include language files, not the base libraries.
	 * @return array|void Will return an array of files if the $return parameter is truthy.
	 */
	public function addI18nJavaScript($langDir, $return = false, $langOnly = false);

	/**
	 * Concatenate several css or javascript files into a single dynamically generated file.
	 * @param string $combinedFileName The filename of the file to save the generated content as.
	 * @param array $files An array of files to be combined.
	 * @param string $media Comma-separated list of media-types (e.g. "screen,projector").
	 */
	public function combineFiles($combinedFileName, $files, $media = null);

	/**
	 * Returns all combined files.
	 * @return array
	 */
	public function getCombineFiles();

	/**
	 * Deletes one or all dynamically generated combined files from the filesystem.
	 * @param string $combinedFileName If left blank, all combined files are deleted.
	 */
	public function deleteCombinedFiles($combinedFileName = null);

	/**
	 * Prevent all combined files from being included.
	 */
	public function clearCombinedFiles();

	/**
	 * Process the list of files to be combined and write the generated files to the filesystem.
	 */
	public function processCombinedFiles();

	/**
	 * Return the content of all custom scripts contatenated.
	 * @return string
	 */
	public function getCustomScripts();

	/**
	 * Show debug information for the registered requirements.
	 */
	public function debug();

}