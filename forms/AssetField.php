<?php

use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\Filesystem\Storage\DBFile;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Security\Permission;



/**
 * Field for uploading into a DBFile instance.
 *
 * This formfield has fewer options than UploadField:
 *  - Assets can only be uploaded, not attached from library
 *  - Duplicate files will only be renamed, not allowed to overwrite existing references.
 *  - Only one file may be attached.
 *  - Files can't be edited once uploaded.
 *  - Attached files can only be removed, not deleted.
 *
 * @package forms
 */
class AssetField extends FileField {

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'upload'
	);

	/**
	 * @var array
	 */
	private static $url_handlers = array(
		'$Action!' => '$Action',
	);

	private static $casting = array(
		'Value' => 'DBFile',
		'UploadFieldThumbnailURL' => 'Varchar'
	);

	/**
	 * Template to use for the file button widget
	 *
	 * @var string
	 */
	protected $templateFileButtons = 'AssetField_FileButtons';

	/**
	 * Parent data record. Will be infered from parent form or controller if blank. The destination
	 * DBFile should be a property of the name $name on this object.
	 *
	 * @var DataObject
	 */
	protected $record;

	/**
	 * Config for this field used in the front-end javascript
	 * (will be merged into the config of the javascript file upload plugin).
	 *
	 * @var array
	 */
	protected $ufConfig = array();

	/**
	 * Front end config defaults
	 *
	 * @config
	 * @var array
	 */
	private static $defaultConfig = array(
		/**
		 * Automatically upload the file once selected
		 *
		 * @var boolean
		 */
		'autoUpload' => true,

		/**
		 * Can the user upload new files.
		 * String values are interpreted as permission codes.
		 *
		 * @var boolean|string
		 */
		'canUpload' => true,

		/**
		 * Shows the target folder for new uploads in the field UI.
		 * Disable to keep the internal filesystem structure hidden from users.
		 *
		 * @var boolean|string
		 */
		'canPreviewFolder' => true,

		/**
		 * Indicate a change event to the containing form if an upload
		 * or file edit/delete was performed.
		 *
		 * @var boolean
		 */
		'changeDetection' => true,

		/**
		 * Maximum width of the preview thumbnail
		 *
		 * @var integer
		 */
		'previewMaxWidth' => 80,

		/**
		 * Maximum height of the preview thumbnail
		 *
		 * @var integer
		 */
		'previewMaxHeight' => 60,

		/**
		 * javascript template used to display uploading files
		 *
		 * @see javascript/UploadField_uploadtemplate.js
		 * @var string
		 */
		'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',

		/**
		 * javascript template used to display already uploaded files
		 *
		 * @see javascript/UploadField_downloadtemplate.js
		 * @var string
		 */
		'downloadTemplateName' => 'ss-uploadfield-downloadtemplate'
	);

	/**
	 * Folder to display in "Select files" list.
	 * Defaults to listing all files regardless of folder.
	 * The folder path should be relative to the webroot.
	 * See {@link FileField->folderName} to set the upload target instead.
	 *
	 * @var string
	 * @example admin/folder/subfolder
	 */
	protected $displayFolderName;

	/**
	 * Construct a new UploadField instance
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 */
	public function __construct($name, $title = null) {
		$this->addExtraClass('ss-upload'); // class, used by js
		$this->addExtraClass('ss-uploadfield'); // class, used by css for uploadfield only

		$this->ufConfig = array_merge($this->ufConfig, self::config()->defaultConfig);

		parent::__construct($name, $title);

		// AssetField always uses rename replacement method
		$this->getUpload()->setReplaceFile(false);

		// filter out '' since this would be a regex problem on JS end
		$this->getValidator()->setAllowedExtensions(
			array_filter(Config::inst()->get('File', 'allowed_extensions'))
		);

		// get the lower max size
		$maxUpload = File::ini2bytes(ini_get('upload_max_filesize'));
		$maxPost = File::ini2bytes(ini_get('post_max_size'));
		$this->getValidator()->setAllowedMaxFileSize(min($maxUpload, $maxPost));
	}

	/**
	 * Set name of template used for Buttons on each file (replace, edit, remove, delete) (without path or extension)
	 *
	 * @param string
	 * @return $this
	 */
	public function setTemplateFileButtons($template) {
		$this->templateFileButtons = $template;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateFileButtons() {
		return $this->templateFileButtons;
	}

	/**
	 * Determine if the target folder for new uploads in is visible the field UI.
	 *
	 * @return boolean
	 */
	public function canPreviewFolder() {
		if(!$this->isActive()) {
			return false;
		}
		$can = $this->getConfig('canPreviewFolder');
		if(is_bool($can)) {
			return $can;
		}
		return Permission::check($can);
	}

	/**
	 * Determine if the target folder for new uploads in is visible the field UI.
	 * Disable to keep the internal filesystem structure hidden from users.
	 *
	 * @param boolean|string $canPreviewFolder Either a boolean flag, or a
	 * required permission code
	 * @return $this Self reference
	 */
	public function setCanPreviewFolder($canPreviewFolder) {
		return $this->setConfig('canPreviewFolder', $canPreviewFolder);
	}

	/**
	 * @param string
	 * @return $this
	 */
	public function setDisplayFolderName($name) {
		$this->displayFolderName = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayFolderName() {
		return $this->displayFolderName;
	}

	/**
	 * Force a record to be used as "Parent" for uploaded Files (eg a Page with a has_one to File)
	 *
	 * @param DataObject $record
	 * @return $this
	 */
	public function setRecord($record) {
		$this->record = $record;
		return $this;
	}

	/**
	 * Get the record to use as "Parent" for uploaded Files (eg a Page with a has_one to File) If none is set, it will
	 * use Form->getRecord().
	 *
	 * @return DataObject
	 */
	public function getRecord() {
		if (!$this->record
			&& $this->form
			&& ($record = $this->form->getRecord())
			&& $record instanceof DataObject
		) {
			$this->record = $record;
		}
		return $this->record;
	}

	public function setValue($value, $record = null) {
		// Extract value from underlying record
		if(empty($value) && $this->getName() && $record instanceof DataObject) {
			$name = $this->getName();
			$value = $record->$name;
		}

		// Convert asset container to tuple value
		if($value instanceof AssetContainer) {
			if($value->exists()) {
				$value = array(
					'Filename' => $value->getFilename(),
					'Hash' => $value->getHash(),
					'Variant' => $value->getVariant()
				);
			} else {
				$value = null;
			}
		}

		// If javascript is disabled, direct file upload (non-html5 style) can
		// trigger a single or multiple file submission. Note that this may be
		// included in addition to re-submitted File IDs as above, so these
		// should be added to the list instead of operated on independently.
		if($uploadedFile = $this->extractUploadedFileData($value)) {
			$value = $this->saveTemporaryFile($uploadedFile, $error);
			if(!$value) {
				throw new ValidationException($error);
			}
		}

		// Set value using parent
		return parent::setValue($value, $record);
	}

	public function Value() {
		// Re-override FileField Value to use data value
		return $this->dataValue();
	}

	public function saveInto(DataObjectInterface $record) {
		// Check required relation details are available
		$name = $this->getName();
		if(!$name) {
			return $this;
		}
		$value = $this->Value();
		foreach(array('Filename', 'Hash', 'Variant') as $part) {
			$partValue = isset($value[$part])
				? $value[$part]
				: null;
			$record->setField("{$name}{$part}", $partValue);
		}
		return $this;
	}

	/**
	 * Assign a front-end config variable for the upload field
	 *
	 * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options for the list of front end options available
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return $this self reference
	 */
	public function setConfig($key, $val) {
		$this->ufConfig[$key] = $val;
		return $this;
	}

	/**
	 * Gets a front-end config variable for the upload field
	 *
	 * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options for the list of front end options available
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key) {
		if(isset($this->ufConfig[$key])) {
			return $this->ufConfig[$key];
		}
	}

	/**
	 * Determine if the field should automatically upload the file.
	 *
	 * @return boolean
	 */
	public function getAutoUpload() {
		return $this->getConfig('autoUpload');
	}

	/**
	 * Determine if the field should automatically upload the file
	 *
	 * @param boolean $autoUpload
	 * @return $this Self reference
	 */
	public function setAutoUpload($autoUpload) {
		return $this->setConfig('autoUpload', $autoUpload);
	}

	/**
	 * Determine if the user has permission to upload.
	 *
	 * @return boolean
	 */
	public function canUpload() {
		if(!$this->isActive()) {
			return false;
		}
		$can = $this->getConfig('canUpload');
		if(is_bool($can)) {
			return $can;
		}
		return Permission::check($can);
	}

	/**
	 * Specify whether the user can upload files.
	 * String values will be treated as required permission codes
	 *
	 * @param bool|string $canUpload Either a boolean flag, or a required
	 * permission code
	 * @return $this Self reference
	 */
	public function setCanUpload($canUpload) {
		return $this->setConfig('canUpload', $canUpload);
	}

	/**
	 * Returns true if the field is neither readonly nor disabled
	 *
	 * @return bool
	 */
	public function isActive() {
		return !$this->isDisabled() && !$this->isReadonly();
	}

	/**
	 * Gets thumbnail width. Defaults to 80
	 *
	 * @return int
	 */
	public function getPreviewMaxWidth() {
		return $this->getConfig('previewMaxWidth');
	}

	/**
	 * Set thumbnail width.
	 *
	 * @param int $previewMaxWidth
	 * @return $this Self reference
	 */
	public function setPreviewMaxWidth($previewMaxWidth) {
		return $this->setConfig('previewMaxWidth', $previewMaxWidth);
	}

	/**
	 * Gets thumbnail height. Defaults to 60
	 *
	 * @return int
	 */
	public function getPreviewMaxHeight() {
		return $this->getConfig('previewMaxHeight');
	}

	/**
	 * Set thumbnail height.
	 *
	 * @param int $previewMaxHeight
	 * @return $this Self reference
	 */
	public function setPreviewMaxHeight($previewMaxHeight) {
		return $this->setConfig('previewMaxHeight', $previewMaxHeight);
	}

	/**
	 * javascript template used to display uploading files
	 * Defaults to 'ss-uploadfield-uploadtemplate'
	 *
	 * @see javascript/UploadField_uploadtemplate.js
	 * @return string
	 */
	public function getUploadTemplateName() {
		return $this->getConfig('uploadTemplateName');
	}

	/**
	 * Set javascript template used to display uploading files
	 *
	 * @param string $uploadTemplateName
	 * @return $this Self reference
	 */
	public function setUploadTemplateName($uploadTemplateName) {
		return $this->setConfig('uploadTemplateName', $uploadTemplateName);
	}

	/**
	 * javascript template used to display already uploaded files
	 * Defaults to 'ss-downloadfield-downloadtemplate'
	 *
	 * @see javascript/DownloadField_downloadtemplate.js
	 * @return string
	 */
	public function getDownloadTemplateName() {
		return $this->getConfig('downloadTemplateName');
	}

	/**
	 * Set javascript template used to display already uploaded files
	 *
	 * @param string $downloadTemplateName
	 * @return $this Self reference
	 */
	public function setDownloadTemplateName($downloadTemplateName) {
		return $this->setConfig('downloadTemplateName', $downloadTemplateName);
	}

	public function extraClass() {
		if($this->isDisabled()) {
			$this->addExtraClass('disabled');
		}
		if($this->isReadonly()) {
			$this->addExtraClass('readonly');
		}

		return parent::extraClass();
	}

	public function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/client/dist/js/ssui.core.js');
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/client/lang');

		Requirements::combine_files('uploadfield.js', array(
			// @todo jquery templates is a project no longer maintained and should be retired at some point.
			THIRDPARTY_DIR . '/javascript-templates/tmpl.js',
			THIRDPARTY_DIR . '/javascript-loadimage/load-image.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/cors/jquery.xdr-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload-ui.js',
			FRAMEWORK_DIR . '/client/dist/js/UploadField_uploadtemplate.js',
			FRAMEWORK_DIR . '/client/dist/js/UploadField_downloadtemplate.js',
			FRAMEWORK_DIR . '/client/dist/js/UploadField.js',
		));
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css'); // TODO hmmm, remove it?
		Requirements::css(FRAMEWORK_DIR . '/client/dist/styles/UploadField.css');

		// Calculated config as per jquery.fileupload-ui.js
		$config = array(
			'allowedMaxFileNumber' => 1, // Only one file allowed for AssetField
			'url' => $this->Link('upload'),
			'urlSelectDialog' => $this->Link('select'),
			'urlAttach' => $this->Link('attach'),
			'urlFileExists' => $this->link('fileexists'),
			'acceptFileTypes' => '.+$',
			// Fileupload treats maxNumberOfFiles as the max number of _additional_ items allowed
			'maxNumberOfFiles' => $this->Value() ? 0 : 1,
			'replaceFile' => false, // Should always be false for AssetField
		);

		// Validation: File extensions
		if ($allowedExtensions = $this->getAllowedExtensions()) {
			$config['acceptFileTypes'] = '(\.|\/)(' . implode('|', $allowedExtensions) . ')$';
			$config['errorMessages']['acceptFileTypes'] = _t(
				'File.INVALIDEXTENSIONSHORT',
				'Extension is not allowed'
			);
		}

		// Validation: File size
		if ($allowedMaxFileSize = $this->getValidator()->getAllowedMaxFileSize()) {
			$config['maxFileSize'] = $allowedMaxFileSize;
			$config['errorMessages']['maxFileSize'] = _t(
				'File.TOOLARGESHORT',
				'Filesize exceeds {size}',
				array('size' => File::format_size($config['maxFileSize']))
			);
		}

		$mergedConfig = array_merge($config, $this->ufConfig);
		return $this->customise(array(
			'ConfigString' => Convert::raw2json($mergedConfig),
			'UploadFieldFileButtons' => $this->renderWith($this->getTemplateFileButtons())
		))->renderWith($this->getTemplates());
	}

	/**
	 * Validation method for this field, called when the entire form is validated
	 *
	 * @param Validator $validator
	 * @return boolean
	 */
	public function validate($validator) {
		$name = $this->getName();
		$value = $this->Value();

		// If there is no file then quit
		if(!$value) {
			return true;
		}

		// Revalidate each file against nested validator
		$this->getUpload()->clearErrors();

		// Generate $_FILES style file attribute array for upload validator
		$store = $this->getAssetStore();
		$mime = $store->getMimeType($value['Filename'], $value['Hash'], $value['Variant']);
		$metadata = $store->getMetadata($value['Filename'], $value['Hash'], $value['Variant']);
		$tmpFile = array(
			'name' => $value['Filename'],
			'type' => $mime,
			'size' => isset($metadata['size']) ? $metadata['size'] : 0,
			'tmp_name' => null, // Should bypass is_uploaded_file check
			'error' => UPLOAD_ERR_OK,
		);
		$this->getUpload()->validate($tmpFile);

		// Check all errors
		if($errors = $this->getUpload()->getErrors()) {
			foreach($errors as $error) {
				$validator->validationError($name, $error, "validation");
			}
			return false;
		}

		return true;
	}

	/**
	 * Given an array of post variables, extract all temporary file data into an array
	 *
	 * @param array $postVars Array of posted form data
	 * @return array data for uploaded file
	 */
	protected function extractUploadedFileData($postVars) {
		// Note: Format of posted file parameters in php is a feature of using
		// <input name='{$Name}[Upload]' /> for multiple file uploads

		// Skip empty file
		if(empty($postVars['tmp_name'])) {
			return null;
		}

		// Return single level array for posted file
		if(empty($postVars['tmp_name']['Upload'])) {
			return $postVars;
		}

		// Extract posted feedback value
		$tmpFile = array();
		foreach(array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
			$tmpFile[$field] = $postVars[$field]['Upload'];
		}
		return $tmpFile;
	}

	/**
	 * Loads the temporary file data into the asset store, and return the tuple details
	 * for the result.
	 *
	 * @param array $tmpFile Temporary file data
	 * @param string $error Error message
	 * @return array Result of saved file, or null if error
	 */
	protected function saveTemporaryFile($tmpFile, &$error = null) {
		$error = null;
		if (empty($tmpFile)) {
			$error = _t('UploadField.FIELDNOTSET', 'File information not found');
			return null;
		}

		if($tmpFile['error']) {
			$error = $tmpFile['error'];
			return null;
		}

		// Get the uploaded file into a new file object.
		try {
			$result = $this
				->getUpload()
				->load($tmpFile, $this->getFolderName());
		} catch (Exception $e) {
			// we shouldn't get an error here, but just in case
			$error = $e->getMessage();
			return null;
		}

		// Check if upload field has an error
		if ($this->getUpload()->isError()) {
			$error = implode(' ' . PHP_EOL, $this->getUpload()->getErrors());
			return null;
		}

		// return tuple array of Filename, Hash and Variant
		return $result;
	}

	/**
	 * Safely encodes the File object with all standard fields required
	 * by the front end
	 *
	 * @param string $filename
	 * @param string $hash
	 * @param string $variant
	 * @return array Encoded list of file attributes
	 */
	protected function encodeAssetAttributes($filename, $hash, $variant) {
		// Force regeneration of file thumbnail for this tuple (without saving into db)
		$object = DBFile::create();
		$object->setValue(array('Filename' => $filename, 'Hash' => $hash, 'Variant' => $variant));

		return array(
			'filename' => $filename,
			'hash' => $hash,
			'variant' => $variant,
			'name' => $object->getBasename(),
			'url' => $object->getURL(),
			'thumbnail_url' => $object->ThumbnailURL(
				$this->getPreviewMaxWidth(),
				$this->getPreviewMaxHeight()
			),
			'size' => $object->getAbsoluteSize(),
			'type' => File::get_file_type($object->getFilename()),
			'buttons' => (string)$this->renderWith($this->getTemplateFileButtons()),
			'fieldname' => $this->getName()
		);
	}

	/**
	 * Action to handle upload of a single file
	 *
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse
	 */
	public function upload(SS_HTTPRequest $request) {
		if($this->isDisabled() || $this->isReadonly() || !$this->canUpload()) {
			return $this->httpError(403);
		}

		// Protect against CSRF on destructive action
		$token = $this
			->getForm()
			->getSecurityToken();
		if(!$token->checkRequest($request)) {
			return $this->httpError(400);
		}

		// Get form details
		$name = $this->getName();
		$postVars = $request->postVar($name);

		// Extract uploaded files from Form data
		$uploadedFile = $this->extractUploadedFileData($postVars);
		if(!$uploadedFile) {
			return $this->httpError(400);
		}

		// Save the temporary files into a File objects
		// and save data/error on a per file basis
		$result = $this->saveTemporaryFile($uploadedFile, $error);
		if(empty($result)) {
			$return = array('error' => $error);
		} else {
			$return = $this->encodeAssetAttributes($result['Filename'], $result['Hash'], $result['Variant']);
		}
		$this
			->getUpload()
			->clearErrors();

		// Format response with json
		$response = new SS_HTTPResponse(Convert::raw2json(array($return)));
		$response->addHeader('Content-Type', 'text/plain');
		return $response;
	}

	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->addExtraClass('readonly');
		$clone->setReadonly(true);
		return $clone;
	}

	/**
	 * Gets the foreign class that needs to be created, or 'File' as default if there
	 * is no relationship, or it cannot be determined.
	 *
	 * @param string $default Default value to return if no value could be calculated
	 * @return string Foreign class name.
	 */
	public function getRelationAutosetClass($default = 'File') {

		// Don't autodetermine relation if no relationship between parent record
		if(!$this->relationAutoSetting) return $default;

		// Check record and name
		$name = $this->getName();
		$record = $this->getRecord();
		if(empty($name) || empty($record)) {
			return $default;
		} else {
			$class = $record->getRelationClass($name);
			return empty($class) ? $default : $class;
		}
	}

	/**
	 * @return AssetStore
	 */
	protected function getAssetStore() {
		return Injector::inst()->get('AssetStore');
	}

}
