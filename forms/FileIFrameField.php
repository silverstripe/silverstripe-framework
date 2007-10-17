<?php 
/**
 * A field that will upload files to a page
 * for use within the CMS.
 */
class FileIFrameField extends FileField {
	
	public function Field() {
		$data = $this->form->getRecord();
		
		if($data->ID && is_numeric($data->ID)) {
			$idxField = $this->name . 'ID';
			$hiddenField =  "<input type=\"hidden\" id=\"" . $this->id() . "\" name=\"$idxField\" value=\"" . $this->attrValue() . "\" />";
			
			$parentClass = $data->class;

			$parentID = $data->ID;
			$parentField = $this->name;
			$iframe = "<iframe name=\"{$this->name}_iframe\" src=\"images/iframe/$parentClass/$parentID/$parentField\" style=\"height: 132px; width: 600px; border-style: none;\"></iframe>";
	
			return $iframe . $hiddenField;
			
		} else {
			$this->value = 'You can add files once you have saved for the first time.';
			return FormField::Field();
		}
	}
	
	public function saveInto(DataObject $record) {
		$fieldName = $this->name . 'ID';
		$hasOnes = $record->has_one($this->name);
		if(!$hasOnes) $hasOnes = $record->has_one($fieldName);
		
		// assume that the file is connected via a has-one
		if( !$hasOnes || !$_FILES[$this->name]['name']){
			return;
		}
		
		$file = new File();
		$file->loadUploaded($_FILES[$this->name]);
		
		$record->$fieldName = $file->ID;	
	}
}
?>