<?php

namespace SilverStripe\Assets;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class Upload_Validator
{

	/**
	 * Contains a list of the max file sizes shared by
	 * all upload fields. This is then duplicated into the
	 * "allowedMaxFileSize" instance property on construct.
	 *
	 * @config
	 * @var array
	 */
	private static $default_max_file_size = array();

	/**
	 * Information about the temporary file produced
	 * by the PHP-runtime.
	 *
	 * @var array
	 */
	protected $tmpFile;

	protected $errors = array();

	/**
	 * Restrict filesize for either all filetypes
	 * or a specific extension, with extension-name
	 * as array-key and the size-restriction in bytes as array-value.
	 *
	 * @var array
	 */
	public $allowedMaxFileSize = array();

	/**
	 * @var array Collection of extensions.
	 * Extension-names are treated case-insensitive.
	 *
	 * Example:
	 * <code>
	 *    array("jpg","GIF")
	 * </code>
	 */
	public $allowedExtensions = array();

	/**
	 * Return all errors that occurred while validating
	 * the temporary file.
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Clear out all errors
	 */
	public function clearErrors()
	{
		$this->errors = array();
	}

	/**
	 * Set information about temporary file produced by PHP.
	 * @param array $tmpFile
	 */
	public function setTmpFile($tmpFile)
	{
		$this->tmpFile = $tmpFile;
	}

	/**
	 * Get maximum file size for all or specified file extension.
	 *
	 * @param string $ext
	 * @return int Filesize in bytes
	 */
	public function getAllowedMaxFileSize($ext = null)
	{

		// Check if there is any defined instance max file sizes
		if (empty($this->allowedMaxFileSize)) {
			// Set default max file sizes if there isn't
			$fileSize = Config::inst()->get('SilverStripe\\Assets\\Upload_Validator', 'default_max_file_size');
			if ($fileSize) {
				$this->setAllowedMaxFileSize($fileSize);
			} else {
				// When no default is present, use maximum set by PHP
				$maxUpload = File::ini2bytes(ini_get('upload_max_filesize'));
				$maxPost = File::ini2bytes(ini_get('post_max_size'));
				$this->setAllowedMaxFileSize(min($maxUpload, $maxPost));
			}
		}

		$ext = strtolower($ext);
		if ($ext) {
			if (isset($this->allowedMaxFileSize[$ext])) {
				return $this->allowedMaxFileSize[$ext];
			}

			$category = File::get_app_category($ext);
			if ($category && isset($this->allowedMaxFileSize['[' . $category . ']'])) {
				return $this->allowedMaxFileSize['[' . $category . ']'];
			}
		}

		return (isset($this->allowedMaxFileSize['*'])) ? $this->allowedMaxFileSize['*'] : false;
	}

	/**
	 * Set filesize maximums (in bytes or INI format).
	 * Automatically converts extensions to lowercase
	 * for easier matching.
	 *
	 * Example:
	 * <code>
	 * array('*' => 200, 'jpg' => 1000, '[doc]' => '5m')
	 * </code>
	 *
	 * @param array|int $rules
	 */
	public function setAllowedMaxFileSize($rules)
	{
		if (is_array($rules) && count($rules)) {
			// make sure all extensions are lowercase
			$rules = array_change_key_case($rules, CASE_LOWER);
			$finalRules = array();

			foreach ($rules as $rule => $value) {
				if (is_numeric($value)) {
					$tmpSize = $value;
				} else {
					$tmpSize = File::ini2bytes($value);
				}

				$finalRules[$rule] = (int)$tmpSize;
			}

			$this->allowedMaxFileSize = $finalRules;
		} elseif (is_string($rules)) {
			$this->allowedMaxFileSize['*'] = File::ini2bytes($rules);
		} elseif ((int)$rules > 0) {
			$this->allowedMaxFileSize['*'] = (int)$rules;
		}
	}

	/**
	 * @return array
	 */
	public function getAllowedExtensions()
	{
		return $this->allowedExtensions;
	}

	/**
	 * Limit allowed file extensions. Empty by default, allowing all extensions.
	 * To allow files without an extension, use an empty string.
	 * See {@link File::$allowed_extensions} to get a good standard set of
	 * extensions that are typically not harmful in a webserver context.
	 * See {@link setAllowedMaxFileSize()} to limit file size by extension.
	 *
	 * @param array $rules List of extensions
	 */
	public function setAllowedExtensions($rules)
	{
		if (!is_array($rules)) {
			return;
		}

		// make sure all rules are lowercase
		foreach ($rules as &$rule) {
			$rule = strtolower($rule);
		}

		$this->allowedExtensions = $rules;
	}

	/**
	 * Determines if the bytesize of an uploaded
	 * file is valid - can be defined on an
	 * extension-by-extension basis in {@link $allowedMaxFileSize}
	 *
	 * @return boolean
	 */
	public function isValidSize()
	{
		// If file was blocked via PHP for being excessive size, shortcut here
		switch ($this->tmpFile['error']) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return false;
		}
		$pathInfo = pathinfo($this->tmpFile['name']);
		$extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : null;
		$maxSize = $this->getAllowedMaxFileSize($extension);
		return (!$this->tmpFile['size'] || !$maxSize || (int)$this->tmpFile['size'] < $maxSize);
	}

	/**
	 * Determine if this file is valid but empty
	 *
	 * @return bool
	 */
	public function isFileEmpty() {
		// Don't check file size for errors
		if ($this->tmpFile['error'] !== UPLOAD_ERR_OK) {
			return false;
		}
		return empty($this->tmpFile['size']);
	}

	/**
	 * Determines if the temporary file has a valid extension
	 * An empty string in the validation map indicates files without an extension.
	 * @return boolean
	 */
	public function isValidExtension()
	{
		$pathInfo = pathinfo($this->tmpFile['name']);

		// Special case for filenames without an extension
		if (!isset($pathInfo['extension'])) {
			return in_array('', $this->allowedExtensions, true);
		} else {
			return (!count($this->allowedExtensions)
				|| in_array(strtolower($pathInfo['extension']), $this->allowedExtensions));
		}
	}

	/**
	 * Run through the rules for this validator checking against
	 * the temporary file set by {@link setTmpFile()} to see if
	 * the file is deemed valid or not.
	 *
	 * @return boolean
	 */
	public function validate()
	{
		// we don't validate for empty upload fields yet
		if (empty($this->tmpFile['name'])) {
			return true;
		}

		// Check file upload
		if (!$this->isValidUpload()) {
			$this->errors[] = _t('File.NOVALIDUPLOAD', 'File is not a valid upload');
			return false;
		}

		// Check file isn't empty
		if ($this->isFileEmpty()) {
			$this->errors[] = _t('File.NOFILESIZE', 'Filesize is zero bytes.');
			return false;
		}

		// filesize validation
		if (!$this->isValidSize()) {
			$pathInfo = pathinfo($this->tmpFile['name']);
			$ext = (isset($pathInfo['extension'])) ? $pathInfo['extension'] : '';
			$arg = File::format_size($this->getAllowedMaxFileSize($ext));
			$this->errors[] = _t(
				'File.TOOLARGE',
				'Filesize is too large, maximum {size} allowed',
				'Argument 1: Filesize (e.g. 1MB)',
				array('size' => $arg)
			);
			return false;
		}

		// extension validation
		if (!$this->isValidExtension()) {
			$this->errors[] = _t(
				'File.INVALIDEXTENSION_SHORT',
				'Extension is not allowed'
			);
			return false;
		}

		return true;
	}

	/**
	 * Check that a valid file was given for upload (ignores file size)
	 *
	 * @return bool
	 */
	public function isValidUpload() {
		// Check file upload
		if ($this->tmpFile['error'] === UPLOAD_ERR_NO_FILE) {
			return false;
		}

		// Check if file is valid uploaded (with exception for unit testing)
		// Note that some "max file size" errors leave "temp_name" empty, so don't fail on this.
		$isRunningTests = (class_exists('SilverStripe\\Dev\\SapphireTest', false) && SapphireTest::is_running_test());
		if (!empty($this->tmpFile['tmp_name']) && !is_uploaded_file($this->tmpFile['tmp_name']) && !$isRunningTests) {
			return false;
		}

		return true;
	}

}
