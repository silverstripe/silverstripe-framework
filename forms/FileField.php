<?php
/**
 * Represents a file type which can be added to a form.
 * Automatically tries to save has_one-relations on the saved
 * record.
 *
 * Please set a validator on the form-object to get feedback
 * about imposed filesize/extension restrictions.
 *
 * See {@link UploadField} For a more full-featured field
 * (incl. ajax-friendly uploads, previews and relationship management).
 *
 * <b>Usage</p>
 *
 * If you want to implement a FileField into a form element, you need to pass it an array of source data.
 *
 * <code>
 * class ExampleForm_Controller extends Page_Controller {
 *
 * 	function Form() {
 * 		$fields = new FieldList(
 * 			new TextField('MyName'),
 * 			new FileField('MyFile')
 * 		);
 * 		$actions = new FieldList(
 * 			new FormAction('doUpload', 'Upload file')
 * 		);
 *    $validator = new RequiredFields(array('MyName', 'MyFile'));
 *
 * 		return new Form($this, 'Form', $fields, $actions, $validator);
 * 	}
 *
 * 	function doUpload($data, $form) {
 * 		$file = $data['MyFile'];
 * 		$content = file_get_contents($file['tmp_name']);
 * 		// ... process content
 * 	}
 * }
 * </code>
 *
 * @package forms
 * @subpackage fields-files
 */

use SilverStripe\Model\DataObjectInterface;
class FileField extends FormField {

	/**
	 * Flag to automatically determine and save a has_one-relationship
	 * on the saved record (e.g. a "Player" has_one "PlayerImage" would
	 * trigger saving the ID of newly created file into "PlayerImageID"
	 * on the record).
	 *
	 * @var boolean
	 */
	public $relationAutoSetting = true;

	/**
	 * Upload object (needed for validation
	 * and actually moving the temporary file
	 * created by PHP).
	 *
	 * @var Upload
	 */
	protected $upload;

	/**
	 * Partial filesystem path relative to /assets directory.
	 * Defaults to Upload::$uploads_folder.
	 *
	 * @var string
	 */
	protected $folderName = false;

	/**
	 * Create a new file field.
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param int $value The value of the field.
	 */
	public function __construct($name, $title = null, $value = null) {
		$this->upload = Upload::create();

		parent::__construct($name, $title, $value);
	}

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		$properties = array_merge($properties, array(
			'MaxFileSize' => $this->getValidator()->getAllowedMaxFileSize()
		));

		return parent::Field($properties);
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('type' => 'file')
		);
	}

	public function saveInto(DataObjectInterface $record) {
		if(!isset($_FILES[$this->name])) {
			return false;
		}

		$fileClass = File::get_class_for_file_extension(
			File::get_file_extension($_FILES[$this->name]['name'], PATHINFO_EXTENSION)
		);

		if($this->relationAutoSetting) {
			// assume that the file is connected via a has-one
			$objectClass = $record->hasOne($this->name);
			if($objectClass === 'File' || empty($objectClass)) {
				// Create object of the appropriate file class
				$file = Object::create($fileClass);
			} else {
				// try to create a file matching the relation
				$file = Object::create($objectClass);
			}
		} else if($record instanceof File) {
			$file = $record;
		} else {
			$file = Object::create($fileClass);
		}

		$this->upload->loadIntoFile($_FILES[$this->name], $file, $this->getFolderName());

		if($this->upload->isError()) {
			return false;
		}

		if($this->relationAutoSetting) {
			if(!$objectClass) {
				return false;
			}

			$file = $this->upload->getFile();

			$record->{$this->name . 'ID'} = $file->ID;
		}

		return $this;
	}

	public function Value() {
		return isset($_FILES[$this->getName()]) ? $_FILES[$this->getName()] : null;
	}

	/**
	 * Get custom validator for this field
	 *
	 * @return Upload_Validator
	 */
	public function getValidator() {
		return $this->upload->getValidator();
	}

	/**
	 * Set custom validator for this field
	 *
	 * @param Upload_Validator $validator
	 * @return $this Self reference
	 */
	public function setValidator($validator) {
		$this->upload->setValidator($validator);
		return $this;
	}

	/**
	 * Sets the upload folder name
	 *
	 * @param string $folderName
	 * @return FileField Self reference
	 */
	public function setFolderName($folderName) {
		$this->folderName = $folderName;
		return $this;
	}

	/**
	 * Gets the upload folder name
	 *
	 * @return string
	 */
	public function getFolderName() {
		return ($this->folderName !== false) ? $this->folderName : Config::inst()->get('Upload', 'uploads_folder');
	}

	public function validate($validator) {
		if(!isset($_FILES[$this->name])) return true;

		$tmpFile = $_FILES[$this->name];

		$valid = $this->upload->validate($tmpFile);
		if(!$valid) {
			$errors = $this->upload->getErrors();
			if($errors) foreach($errors as $error) {
				$validator->validationError($this->name, $error, "validation", false);
			}
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the Upload handler
	 *
	 * @return Upload
	 */
	public function getUpload() {
		return $this->upload;
	}

	/**
	 * Sets the upload handler
	 *
	 * @param Upload $upload
	 * @return FileField Self reference
	 */
	public function setUpload(Upload $upload) {
		$this->upload = $upload;
		return $this;
	}

	/**
	 * Limit allowed file extensions. Empty by default, allowing all extensions.
	 * To allow files without an extension, use an empty string.
	 * See {@link File::$allowed_extensions} to get a good standard set of
	 * extensions that are typically not harmful in a webserver context.
	 * See {@link setAllowedMaxFileSize()} to limit file size by extension.
	 *
	 * @param array $rules List of extensions
	 * @return $this
	 */
	public function setAllowedExtensions($rules) {
		$this->getValidator()->setAllowedExtensions($rules);
		return $this;
	}

	/**
	 * Limit allowed file extensions by specifying categories of file types.
	 * These may be 'image', 'image/supported', 'audio', 'video', 'archive', 'flash', or 'document'
	 * See {@link File::$allowed_extensions} for details of allowed extensions
	 * for each of these categories
	 *
	 * @param string $category Category name
	 * @param string,... $categories Additional category names
	 * @return $this
	 */
	public function setAllowedFileCategories($category) {
		$extensions = File::get_category_extensions(func_get_args());
		return $this->setAllowedExtensions($extensions);
	}

	/**
	 * Returns list of extensions allowed by this field, or an empty array
	 * if there is no restriction
	 *
	 * @return array
	 */
	public function getAllowedExtensions() {
		return $this->getValidator()->getAllowedExtensions();
	}

}
