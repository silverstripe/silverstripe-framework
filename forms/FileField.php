<?php
/**
 * Represents a file type which can be added to a form.
 * Automatically tries to save has_one-relations on the saved
 * record.
 * 
 * Please set a validator on the form-object to get feedback
 * about imposed filesize/extension restrictions.
 * 
 * CAUTION: Doesn't work in the CMS due to ajax submission, please use {@link FileIFrameField} instead.
 * 
 * <b>Usage</p>
 * 
 * If you want to implement a FileField into a form element, you need to pass it an array of source data.
 * 
 * <code>
 * class ExampleForm_Controller extends Page_Controller {
 * 
 * 	public function Form() {
 * 		$fields = new FieldSet(
 * 			new TextField('MyName'),
 * 			new FileField('MyFile')
 * 		);
 * 		$actions = new FieldSet(
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
	 * @param Form $form Reference to the container form
	 * @param string $rightTitle Used in SmallFieldHolder() to force a right-aligned label
	 * @param string $folderName Folder to upload files to
	 */
	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null, $folderName = null) {
		if(isset($folderName)) $this->folderName = $folderName;
		$this->upload = new Upload();
	
		parent::__construct($name, $title, $value, $form, $rightTitle);
	}

	public function Field() {
		return $this->createTag(
			'input', 
			array(
				"type" => "file", 
				"name" => $this->name, 
				"id" => $this->id(),
				"tabindex" => $this->getTabIndex()
			)
		) . 
		$this->createTag(
			'input', 
		  	array(
		  		"type" => "hidden", 
		  		"name" => "MAX_FILE_SIZE", 
		  		"value" => $this->getValidator()->getAllowedMaxFileSize(),
				"tabindex" => $this->getTabIndex()
		  	)
		);
	}
	
	public function saveInto(DataObject $record) {
		if(!isset($_FILES[$this->name])) return false;
		
		if($this->relationAutoSetting) {
			// assume that the file is connected via a has-one
			$hasOnes = $record->has_one($this->name);
			// try to create a file matching the relation
			$file = (is_string($hasOnes)) ? Object::create($hasOnes) : new File(); 
		} else {
			$file = new File();
		}
		
		$this->upload->loadIntoFile($_FILES[$this->name], $file, $this->folderName);
		if($this->upload->isError()) return false;
		
		$file = $this->upload->getFile();
		
		if($this->relationAutoSetting) {
			if(!$hasOnes) return false;
			
			// save to record
			$record->{$this->name . 'ID'} = $file->ID;
		}
	}
	
	public function Value() {
		return $_FILES[$this->Name()];
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
	}
	
	/**
	 * Get maximum file size for all or specified file extension.
	 * Falls back to the default filesize restriction ('*')
	 * if the extension was not found.
	 *
	 * @deprecated 2.5
	 * @param string $ext
	 * @return int Filesize in bytes (0 means no filesize set)
	 */
	public function getAllowedMaxFileSize($ext = null) {
		user_error('Upload::getAllowedMaxFileSize() is deprecated. Please use Upload_Validator::getAllowedMaxFileSize() instead', E_USER_NOTICE);
		$this->getValidator()->getAllowedMaxFileSize($ext);
	}
	
	/**
	 * Set filesize maximums (in bytes).
	 * Automatically converts extensions to lowercase
	 * for easier matching.
	 * 
	 * Example: 
	 * <code>
	 * array('*' => 200, 'jpg' => 1000)
	 * </code>
	 *
	 * @deprecated 2.5
	 * @param unknown_type $rules
	 */
	public function setAllowedMaxFileSize($rules) {
		user_error('Upload::setAllowedMaxFileSize() is deprecated. Please use Upload_Validator::setAllowedMaxFileSize() instead', E_USER_NOTICE);
		$this->getValidator()->setAllowedMaxFileSize($rules);
	}
	
	/**
	 * @deprecated 2.5
	 * @return array
	 */
	public function getAllowedExtensions() {
		user_error('Upload::getAllowedExtensions() is deprecated. Please use Upload_Validator::getAllowedExtensions() instead', E_USER_NOTICE);
		return $this->getValidator()->getAllowedExtensions();
	}
	
	/**
	 * @deprecated 2.5
	 * @param array $rules
	 */
	public function setAllowedExtensions($rules) {
		user_error('Upload::setAllowedExtensions() is deprecated. Please use Upload_Validator::setAllowedExtensions() instead', E_USER_NOTICE);
		$this->getValidator()->setAllowedExtensions($rules);
	}
	
	/**
	 * @param string $folderName
	 */
	public function setFolderName($folderName) {
		$this->folderName = $folderName;
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
}
?>