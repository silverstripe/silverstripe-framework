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
 * @package forms
 * @subpackage fields-files
 */
class FileField extends FormField {
	
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
	 * 
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
		  		"value" => $this->getAllowedMaxFileSize(),
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
		
		$this->upload->setAllowedExtensions($this->allowedExtensions);
		$this->upload->setAllowedMaxFileSize($this->allowedMaxFileSize);
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
	 * Get maximum file size for all or specified file extension.
	 * Falls back to the default filesize restriction ('*')
	 * if the extension was not found.
	 *
	 * @param string $ext
	 * @return int Filesize in bytes (0 means no filesize set)
	 */
	public function getAllowedMaxFileSize($ext = null) {
		$ext = strtolower($ext);
		if(isset($ext) && isset($this->allowedMaxFileSize[$ext])) {
			return $this->allowedMaxFileSize[$ext];   
		} else {
			return (isset($this->allowedMaxFileSize['*'])) ? $this->allowedMaxFileSize['*'] : 0;
		}
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
	 * @param unknown_type $rules
	 */
	public function setAllowedMaxFileSize($rules) {
		if(is_array($rules)) {
			// make sure all extensions are lowercase
			$rules = array_change_key_case($rules, CASE_LOWER);
			$this->allowedMaxFileSize = $rules;
		} else {
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
	 * @param array $rules
	 */
	public function setAllowedExtensions($rules) {
		if(!is_array($rules)) return false;
		
		// make sure all rules are lowercase
		foreach($rules as &$rule) $rule = strtolower($rule);
		
		$this->allowedExtensions = $rules;
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
		$this->upload->setAllowedExtensions($this->allowedExtensions);
		$this->upload->setAllowedMaxFileSize($this->allowedMaxFileSize);

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