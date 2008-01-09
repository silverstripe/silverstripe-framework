<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableFileField
 * Allows a user to add a field that can be used to upload a file
 * @package forms
 * @subpackage fieldeditor
 */
class EditableFileField extends EditableFormField {
	
	// this needs to be moved.
	static $has_one = array(
		"UploadedFile" => "File"
	);
	
	// TODO Interface and properties for these properties
	static $file_size_restrictions = array();
	static $allowed_file_types = array();
	
	static $singular_name = 'File field';
	static $plural_names = 'File fields';
	
	function ExtraOptions() {
		return parent::ExtraOptions();
	}
	
	function getFormField() {
		if( $field = parent::getFormField() )
			return $field;
			return new FileField($this->Name, $this->Title, $this->getField('Default'));
			// TODO We can't use the preview feature because FileIFrameField also shows the "From the file store" functionality
			//return new FileIFrameField( $this->Name, $this->Title, $this->getField('Default') );
	}
	
	function getSimpleFormField(){
		return new FileField($this->Name, $this->Title, $this->getField('Default'));
	}
	
	function createSubmittedField($data, $submittedForm, $fieldClass = "SubmittedFileField" ) {
		if( !$_FILES[$this->Name] )
			return null;
		
		$submittedField = new $fieldClass();
		$submittedField->Title = $this->Title;
		$submittedField->Name = $this->Name;
		$submittedField->ParentID = $submittedForm->ID;
			
		// create the file from post data
		$uploadedFile = new File();
		$uploadedFile->set_stat('file_size_restrictions',$this->stat('file_size_restrictions'));
		$uploadedFile->set_stat('allowed_file_types',$this->stat('allowed_file_types'));
		$uploadedFile->loadUploaded( $_FILES[$this->Name] );
		$submittedField->UploadedFileID = $uploadedFile->ID;
		$submittedField->write();
		return $submittedField;
	}
}
?>