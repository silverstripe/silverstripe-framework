<?php

use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetNameGenerator;
use SilverStripe\Filesystem\Storage\AssetStore;

/**
 * Manages uploads via HTML forms processed by PHP,
 * uploads to Silverstripe's default upload directory,
 * and either creates a new or uses an existing File-object
 * for syncing with the database.
 *
 * <b>Validation</b>
 *
 * By default, a user can upload files without extension limitations,
 * which can be a security risk if the webserver is not properly secured.
 * Use {@link setAllowedExtensions()} to limit this list,
 * and ensure the "assets/" directory does not execute scripts
 * (see http://doc.silverstripe.org/secure-development#filesystem).
 * {@link File::$allowed_extensions} provides a good start for a list of "safe" extensions.
 *
 * @package framework
 * @subpackage filesystem
 *
 * @todo Allow for non-database uploads
 */
class Upload extends Controller {

	private static $allowed_actions = array(
		'index',
		'load'
	);

	/**
	 * A dataobject (typically {@see File}) which implements {@see AssetContainer}
	 *
	 * @var AssetContainer
	 */
	protected $file;

	/**
	 * Validator for this upload field
	 *
	 * @var Upload_Validator
	 */
	protected $validator;

	/**
	 * Information about the temporary file produced
	 * by the PHP-runtime.
	 *
	 * @var array
	 */
	protected $tmpFile;

	/**
	 * Replace an existing file rather than renaming the new one.
	 *
	 * @var boolean
	 */
	protected $replaceFile;

	/**
	 * Processing errors that can be evaluated,
	 * e.g. by Form-validation.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * A foldername relative to /assets,
	 * where all uploaded files are stored by default.
	 *
	 * @config
	 * @var string
	 */
	private static $uploads_folder = "Uploads";

	/**
	 * A prefix for the version number added to an uploaded file
	 * when a file with the same name already exists.
	 * Example using no prefix: IMG001.jpg becomes IMG2.jpg
	 * Example using '-v' prefix: IMG001.jpg becomes IMG001-v2.jpg
	 *
	 * @config
	 * @var string
	 */
	private static $version_prefix = '-v';

	public function __construct() {
		parent::__construct();
		$this->validator = Injector::inst()->create('Upload_Validator');
		$this->replaceFile = self::config()->replaceFile;
	}

	/**
	 * Get current validator
	 *
	 * @return Upload_Validator $validator
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Set a different instance than {@link Upload_Validator}
	 * for this upload session.
	 *
	 * @param object $validator
	 */
	public function setValidator($validator) {
		$this->validator = $validator;
	}


	/**
	 * Get an asset renamer for the given filename.
	 *
	 * @param string $filename Path name
	 * @return AssetNameGenerator
	 */
	protected function getNameGenerator($filename){
		return Injector::inst()->createWithArgs('AssetNameGenerator', array($filename));
	}

	/**
	 * Save an file passed from a form post into this object.
	 * File names are filtered through {@link FileNameFilter}, see class documentation
	 * on how to influence this behaviour.
	 *
	 * @param $tmpFile array Indexed array that PHP generated for every file it uploads.
	 * @param $folderPath string Folder path relative to /assets
	 * @return Boolean|string Either success or error-message.
	 */
	public function load($tmpFile, $folderPath = false) {
		if(!is_array($tmpFile)) {
			throw new InvalidArgumentException(
				"Upload::load() Not passed an array.  Most likely, the form hasn't got the right enctype"
			);
		}

		// Validate
		$this->clearErrors();
		$valid = $this->validate($tmpFile);
		if(!$valid) {
			return false;
		}

		// Clean filename
		if(!$folderPath) {
			$folderPath = $this->config()->uploads_folder;
		}
		$nameFilter = FileNameFilter::create();
		$file = $nameFilter->filter($tmpFile['name']);
		$filename = basename($file);
		if($folderPath) {
			$filename = File::join_paths($folderPath, $filename);
		}

		// Validate filename
		$filename = $this->resolveExistingFile($filename);

		// Save file into backend
		$conflictResolution = $this->replaceFile ? AssetStore::CONFLICT_OVERWRITE : AssetStore::CONFLICT_RENAME;
		$this->file->setFromLocalFile($tmpFile['tmp_name'], $filename, null, null, $conflictResolution);
		
		// Save changes to underlying record (if it's a DataObject)
		if($this->file instanceof DataObject) {
			$this->file->write();
		}
		
		//to allow extensions to e.g. create a version after an upload
		$this->file->extend('onAfterUpload');
		$this->extend('onAfterLoad', $this->file);
		return true;
	}

	/**
	 * Given a file and filename, ensure that file renaming / replacing rules are satisfied
	 *
	 * If replacing, this method may replace $this->file with an existing record to overwrite.
	 * If renaming, a new value for $filename may be returned
	 *
	 * @param string $filename
	 * @return string $filename A filename safe to write to
	 */
	protected function resolveExistingFile($filename) {
		// Create a new file record (or try to retrieve an existing one)
		if(!$this->file) {
			$fileClass = File::get_class_for_file_extension(
				File::get_file_extension($filename)
			);
			$this->file = $fileClass::create();
		}

		// Skip this step if not writing File dataobjects
		if(! ($this->file instanceof File) ) {
			return $filename;
		}

		// Check there is if existing file
		$existing = File::find($filename);

		// If replacing (or no file exists) confirm this filename is safe
		if($this->replaceFile || !$existing) {
			// If replacing files, make sure to update the OwnerID
			if(!$this->file->ID && $this->replaceFile && $existing) {
				$this->file = $existing;
				$this->file->OwnerID = Member::currentUserID();
			}
			// Filename won't change if replacing
			return $filename;
		}

		// if filename already exists, version the filename (e.g. test.gif to test-v2.gif, test-v2.gif to test-v3.gif)
		$renamer = $this->getNameGenerator($filename);
		foreach($renamer as $newName) {
			if(!File::find($newName)) {
				return $newName;
			}
		}

		// Fail
		$tries = $renamer->getMaxTries();
		throw new Exception("Could not rename {$filename} with {$tries} tries");
	}

	/**
	 * Load temporary PHP-upload into File-object.
	 *
	 * @param array $tmpFile
	 * @param AssetContainer $file
	 * @return Boolean
	 */
	public function loadIntoFile($tmpFile, $file, $folderPath = false) {
		$this->file = $file;
		return $this->load($tmpFile, $folderPath);
	}

	/**
	 * @return Boolean
	 */
	public function setReplaceFile($bool) {
		$this->replaceFile = $bool;
	}

	/**
	 * @return Boolean
	 */
	public function getReplaceFile() {
		return $this->replaceFile;
	}

	/**
	 * Container for all validation on the file
	 * (e.g. size and extension restrictions).
	 * Is NOT connected to the {Validator} classes,
	 * please have a look at {FileField->validate()}
	 * for an example implementation of external validation.
	 *
	 * @param array $tmpFile
	 * @return boolean
	 */
	public function validate($tmpFile) {
		$validator = $this->validator;
		$validator->setTmpFile($tmpFile);
		$isValid = $validator->validate();
		if($validator->getErrors()) {
			$this->errors = array_merge($this->errors, $validator->getErrors());
		}
		return $isValid;
	}

	/**
	 * Get file-object, either generated from {load()},
	 * or manually set.
	 *
	 * @return AssetContainer
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Set a file-object (similiar to {loadIntoFile()})
	 *
	 * @param AssetContainer $file
	 */
	public function setFile(AssetContainer $file) {
		$this->file = $file;
	}

	/**
	 * Clear out all errors (mostly set by {loadUploaded()})
	 * including the validator's errors
	 */
	public function clearErrors() {
		$this->errors = array();
		$this->validator->clearErrors();
	}

	/**
	 * Determines wether previous operations caused an error.
	 *
	 * @return boolean
	 */
	public function isError() {
		return (count($this->errors));
	}

	/**
	 * Return all errors that occurred while processing so far
	 * (mostly set by {loadUploaded()})
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

}

/**
 * @package framework
 * @subpackage filesystem
 */
class Upload_Validator {

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
	 * 	array("jpg","GIF")
	 * </code>
	 */
	public $allowedExtensions = array();

	/**
	 * Return all errors that occurred while validating
	 * the temporary file.
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Clear out all errors
	 */
	public function clearErrors() {
		$this->errors = array();
	}

	/**
	 * Set information about temporary file produced by PHP.
	 * @param array $tmpFile
	 */
	public function setTmpFile($tmpFile) {
		$this->tmpFile = $tmpFile;
	}

	/**
	 * Get maximum file size for all or specified file extension.
	 *
	 * @param string $ext
	 * @return int Filesize in bytes
	 */
	public function getAllowedMaxFileSize($ext = null) {
		
		// Check if there is any defined instance max file sizes
		if (empty($this->allowedMaxFileSize)) {
			// Set default max file sizes if there isn't
			$fileSize = Config::inst()->get('Upload_Validator', 'default_max_file_size');
			if (isset($fileSize)) {
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
			
			return false;
		} else {
			return (isset($this->allowedMaxFileSize['*'])) ? $this->allowedMaxFileSize['*'] : false;
		}
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
	public function setAllowedMaxFileSize($rules) {
		if(is_array($rules) && count($rules)) {
			// make sure all extensions are lowercase
			$rules = array_change_key_case($rules, CASE_LOWER);
			$finalRules = array();
			$tmpSize = 0;
			
			foreach ($rules as $rule => $value) {
				if (is_numeric($value)) {
					$tmpSize = $value;
				} else {
					$tmpSize = File::ini2bytes($value);
				}
			
				$finalRules[$rule] = (int)$tmpSize;
			}
			
			$this->allowedMaxFileSize = $finalRules;
		} elseif(is_string($rules)) {
			$this->allowedMaxFileSize['*'] = File::ini2bytes($rules);
		} elseif((int) $rules > 0) {
			$this->allowedMaxFileSize['*'] = (int)$rules;
		}
	}

	/**
	 * @return array
	 */
	public function getAllowedExtensions() {
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
	public function setAllowedExtensions($rules) {
		if(!is_array($rules)) return false;

		// make sure all rules are lowercase
		foreach($rules as &$rule) $rule = strtolower($rule);

		$this->allowedExtensions = $rules;
	}

	/**
	 * Determines if the bytesize of an uploaded
	 * file is valid - can be defined on an
	 * extension-by-extension basis in {@link $allowedMaxFileSize}
	 *
	 * @return boolean
	 */
	public function isValidSize() {
		$pathInfo = pathinfo($this->tmpFile['name']);
		$extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : null;
		$maxSize = $this->getAllowedMaxFileSize($extension);
		return (!$this->tmpFile['size'] || !$maxSize || (int) $this->tmpFile['size'] < $maxSize);
	}

	/**
	 * Determines if the temporary file has a valid extension
	 * An empty string in the validation map indicates files without an extension.
	 * @return boolean
	 */
	public function isValidExtension() {
		$pathInfo = pathinfo($this->tmpFile['name']);

		// Special case for filenames without an extension
		if(!isset($pathInfo['extension'])) {
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
	public function validate() {
		// we don't validate for empty upload fields yet
		if(empty($this->tmpFile['name']) || empty($this->tmpFile['tmp_name'])) {
			return true;
		}

		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		if(isset($this->tmpFile['tmp_name']) && !is_uploaded_file($this->tmpFile['tmp_name']) && !$isRunningTests) {
			$this->errors[] = _t('File.NOVALIDUPLOAD', 'File is not a valid upload');
			return false;
		}
		
		// Check file isn't empty
		if(empty($this->tmpFile['size']) || !filesize($this->tmpFile['tmp_name'])) {
			$this->errors[] = _t('File.NOFILESIZE', 'Filesize is zero bytes.');
			return false;
		}

		$pathInfo = pathinfo($this->tmpFile['name']);
		// filesize validation
		if(!$this->isValidSize()) {
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
		if(!$this->isValidExtension()) {
			$this->errors[] = _t(
				'File.INVALIDEXTENSION',
				'Extension is not allowed (valid: {extensions})',
				'Argument 1: Comma-separated list of valid extensions',
				array('extensions' => wordwrap(implode(', ', $this->allowedExtensions)))
			);
			return false;
		}

		return true;
	}

}
