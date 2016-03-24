<?php
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
	 * A File object
	 *
	 * @var File
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
	private static $version_prefix = ''; // a default value will be introduced in SS4.0

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
	 * Save an file passed from a form post into this object.
	 * File names are filtered through {@link FileNameFilter}, see class documentation
	 * on how to influence this behaviour.
	 *
	 * @param $tmpFile array Indexed array that PHP generated for every file it uploads.
	 * @param $folderPath string Folder path relative to /assets
	 * @return Boolean|string Either success or error-message.
	 */
	public function load($tmpFile, $folderPath = false) {
		$this->clearErrors();

		if(!$folderPath) $folderPath = $this->config()->uploads_folder;

		if(!is_array($tmpFile)) {
			user_error("Upload::load() Not passed an array.  Most likely, the form hasn't got the right enctype",
				E_USER_ERROR);
		}

		if(!$tmpFile['size']) {
			$this->errors[] = _t('File.NOFILESIZE', 'File size is zero bytes.');
			return false;
		}

		$valid = $this->validate($tmpFile);
		if(!$valid) return false;

		// @TODO This puts a HUGE limitation on files especially when lots
		// have been uploaded.
		$base = Director::baseFolder();
		$parentFolder = Folder::find_or_make($folderPath);

		// Generate default filename
		$nameFilter = FileNameFilter::create();
		$file = $nameFilter->filter($tmpFile['name']);
		$fileName = basename($file);

		$relativeFolderPath = $parentFolder
				? $parentFolder->getRelativePath()
				: ASSETS_DIR . '/';
		$relativeFilePath = $relativeFolderPath . $fileName;

		// Create a new file record (or try to retrieve an existing one)
		if(!$this->file) {
			$fileClass = File::get_class_for_file_extension(pathinfo($tmpFile['name'], PATHINFO_EXTENSION));
			$this->file = new $fileClass();
		}
		if(!$this->file->ID && $this->replaceFile) {
			$fileClass = $this->file->class;
			$file = File::get()
				->filter(array(
					'ClassName' => $fileClass,
					'Name' => $fileName,
					'ParentID' => $parentFolder ? $parentFolder->ID : 0
				))->First();
			if($file) {
				$this->file = $file;
			}
		}

		// if filename already exists, version the filename (e.g. test.gif to test2.gif, test2.gif to test3.gif)
		if(!$this->replaceFile) {
			$fileSuffixArray = explode('.', $fileName);
			$fileTitle = array_shift($fileSuffixArray);
			$fileSuffix = !empty($fileSuffixArray)
					? '.' . implode('.', $fileSuffixArray)
					: null;

			// make sure files retain valid extensions
			$oldFilePath = $relativeFilePath;
			$relativeFilePath = $relativeFolderPath . $fileTitle . $fileSuffix;
			if($oldFilePath !== $relativeFilePath) {
				user_error("Couldn't fix $relativeFilePath", E_USER_ERROR);
			}
			while(file_exists("$base/$relativeFilePath")) {
				$i = isset($i) ? ($i+1) : 2;
				$oldFilePath = $relativeFilePath;

				$prefix = $this->config()->version_prefix;
				$pattern = '/' . preg_quote($prefix) . '([0-9]+$)/';
				if(preg_match($pattern, $fileTitle, $matches)) {
					$fileTitle = preg_replace($pattern, $prefix . ($matches[1] + 1), $fileTitle);
				} else {
					$fileTitle .= $prefix . $i;
				}
				$relativeFilePath = $relativeFolderPath . $fileTitle . $fileSuffix;

				if($oldFilePath == $relativeFilePath && $i > 2) {
					user_error("Couldn't fix $relativeFilePath with $i tries", E_USER_ERROR);
				}
			}
		} else {
			//reset the ownerID to the current member when replacing files
			$this->file->OwnerID = (Member::currentUser() ? Member::currentUser()->ID : 0);
		}

		if(file_exists($tmpFile['tmp_name']) && copy($tmpFile['tmp_name'], "$base/$relativeFilePath")) {
			$this->file->ParentID = $parentFolder ? $parentFolder->ID : 0;
			// This is to prevent it from trying to rename the file
			$this->file->Name = basename($relativeFilePath);
			$this->file->write();
			$this->file->onAfterUpload();
			$this->extend('onAfterLoad', $this->file, $tmpFile);   //to allow extensions to e.g. create a version after an upload
			return true;
		} else {
			$this->errors[] = _t('File.NOFILESIZE', 'File size is zero bytes.');
			return false;
		}
	}

	/**
	 * Load temporary PHP-upload into File-object.
	 *
	 * @param array $tmpFile
	 * @param File $file
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
	 * @return File
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Set a file-object (similiar to {loadIntoFile()})
	 *
	 * @param File $file
	 */
	public function setFile($file) {
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
		if(!isset($this->tmpFile['name']) || empty($this->tmpFile['name'])) return true;

		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		if(isset($this->tmpFile['tmp_name']) && !is_uploaded_file($this->tmpFile['tmp_name']) && !$isRunningTests) {
			$this->errors[] = _t('File.NOVALIDUPLOAD', 'File is not a valid upload');
			return false;
		}

		$pathInfo = pathinfo($this->tmpFile['name']);
		// filesize validation
		if(!$this->isValidSize()) {
			$ext = (isset($pathInfo['extension'])) ? $pathInfo['extension'] : '';
			$arg = File::format_size($this->getAllowedMaxFileSize($ext));
			$this->errors[] = _t(
				'File.TOOLARGE',
				'File size is too large, maximum {size} allowed',
				'Argument 1: File size (e.g. 1MB)',
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
