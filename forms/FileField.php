<?php
/**
 * Represents a file type which can be added to a form.
 */
class FileField extends FormField {
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
		$file->loadUploaded($_FILES[$this->name]);
		
		$record->$fieldName = $file->ID;	
	}
	
	public function Value() {
		return $_FILES[$this->Name()];
	}
}
?>