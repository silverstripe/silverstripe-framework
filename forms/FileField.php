<?php

/**
 * @package forms
 * @subpackage fields-files
 */

/**
 * Represents a file type which can be added to a form.
 * @package forms
 * @subpackage fields-files
 */
class FileField extends FormField {
	/**
	 * Create a new file field.
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param int $value The value of the field.
	 * @param Form $form Reference to the container form
	 * @param string $rightTitle Used in SmallFieldHolder() to force a right-aligned label
	 * @param string $folderName Folder to upload files to
	 */
	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null, $folderName = 'Uploads') {
		$this->folderName = $folderName;
	
		parent::__construct($name, $title, $value, $form, $rightTitle);
	}

	public function Field() {
		return 
		   $this->createTag("input", array("type" => "file", "name" => $this->name, "id" => $this->id())) . 
		   $this->createTag("input", array("type" => "hidden", "name" => "MAX_FILE_SIZE", "value" => 30*1024*1024));
	}
	
	public function saveInto(DataObject $record) {
		$fieldName = $this->name . 'ID';
		$hasOnes = $record->has_one(/*$fieldName*/$this->name);
		
		// assume that the file is connected via a has-one
		if( !$hasOnes )
			return;
		
		$file = new File();
		$file->loadUploaded($_FILES[$this->name], $this->folderName);
		
		$record->$fieldName = $file->ID;	
	}
	
	public function Value() {
		return $_FILES[$this->Name()];
	}
}
?>
