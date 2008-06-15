<?php
/**
 * Allows a user to add a field that can be used to upload a file 
 * @package forms
 * @subpackage fieldeditor
 */
class EditableFileField extends EditableFormField {
	
	// this needs to be moved.
	static $has_one = array(
		"UploadedFile" => "File"
	);
	
	/**
	 * @see {Upload->allowedMaxFileSize}
	 * @var int
	 */
	public static $allowed_max_file_size;
	
	/**
	 * @see {Upload->allowedExtensions}
	 * @var array
	 */
	public static $allowed_extensions = array();
	
	static $singular_name = 'File field';
	static $plural_names = 'File fields';
	
	function getFormField() {
		if($field = parent::getFormField())
			return $field;
			return new FileField($this->Name, $this->Title, $this->getField('Default'));
			// TODO We can't use the preview feature because FileIFrameField also shows the "From the file store" functionality
			//return new FileIFrameField( $this->Name, $this->Title, $this->getField('Default') );
	}
	
	function getSimpleFormField(){
		return new FileField($this->Name, $this->Title, $this->getField('Default'));
	}
	
	function createSubmittedField($data, $submittedForm, $fieldClass = "SubmittedFileField") {
		if(!$_FILES[$this->Name])
			return null;
		
		$submittedField = new $fieldClass();
		$submittedField->Title = $this->Title;
		$submittedField->Name = $this->Name;
		$submittedField->ParentID = $submittedForm->ID;
			
		// create the file from post data
		$upload = new Upload();
		$upload->setAllowedExtensions(self::$allowed_extensions);
		$upload->setAllowedMaxFileSize(self::$allowed_max_file_size);

		// upload file
		$upload->load($_FILES[$this->Name]);
		
		$uploadedFile = $upload->getFile();
		$submittedField->UploadedFileID = $uploadedFile->ID;
		$submittedField->write();
		
		return $submittedField;
	}
}
?>