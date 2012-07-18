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
 * 	public function Form() {
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
class FileField extends FormField {
	
	/**
	 * Restrict filesize for either all filetypes
	 * or a specific extension, with extension-name
	 * as array-key and the size-restriction in bytes as array-value.
	 *
	 * @deprecated 2.5
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
	 *
	 * @deprecated 2.5
	 * @var array
	 */
	public $allowedExtensions = array();
	
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
	 * Defaults to 'Uploads'.
	 *
	 * @var string
	 */
	protected $folderName = 'Uploads';
	
	/**
	 * Create a new file field.
	 * 
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param int $value The value of the field.
	 */
	function __construct($name, $title = null, $value = null) {
		if(count(func_get_args()) > 3) {
			Deprecation::notice(
				'3.0', 
				'Use setRightTitle() and setFolderName() instead of constructor arguments', 
				Deprecation::SCOPE_GLOBAL
			);
		}

		$this->upload = new Upload();
	
		parent::__construct($name, $title, $value);
	}

	public function Field($properties = array()) {
		$properties = array_merge($properties, array(
			'MaxFileSize' => $this->getValidator()->getAllowedMaxFileSize()
		));
		
		return parent::Field($properties);
	}

	function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('type' => 'file')
		);
	}

	public function saveInto(DataObjectInterface $record) {
		if(!isset($_FILES[$this->name])) return false;
		$fileClass = File::get_class_for_file_extension(pathinfo($_FILES[$this->name]['name'], PATHINFO_EXTENSION));
		
		if($this->relationAutoSetting) {
			// assume that the file is connected via a has-one
			$hasOnes = $record->has_one($this->name);
			// try to create a file matching the relation
			$file = (is_string($hasOnes)) ? Object::create($hasOnes) : new $fileClass(); 
		} else {
			$file = new $fileClass();
		}
		
		$this->upload->loadIntoFile($_FILES[$this->name], $file, $this->folderName);
		if($this->upload->isError()) return false;
		
		$file = $this->upload->getFile();
		
		if($this->relationAutoSetting) {
			if(!$hasOnes) return false;
			
			// save to record
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
	 * @param object $validator
	 */
	public function getValidator() {
		return $this->upload->getValidator();
	}
	
	/**
	 * Set custom validator for this field
	 * 
	 * @param object $validator
	 */
	public function setValidator($validator) {
		$this->upload->setValidator($validator);
		return $this;
	}
	
	/**
	 * @param string $folderName
	 */
	public function setFolderName($folderName) {
		$this->folderName = $folderName;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getFolderName() {
		return $this->folderName;
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
	 * @return Upload
	 */
	public function getUpload() {
		return $this->upload;
	}

	public function setUpload(Upload $upload) {
		$this->upload = $upload;
	}

}