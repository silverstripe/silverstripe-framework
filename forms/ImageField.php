<?php

/**
 * A field that will upload attached images.
 */
class ImageField extends FileField {
	
	public function Field($id = null) {
		$data = $this->form->getRecord();
		
		$parentID = ($id && is_numeric($id)) ? $id : (isset($data) ? $data->ID : 0);
		if($data && $parentID && is_numeric($parentID)) {
			$idxField = $this->name . 'ID';
			$hiddenField = "<input class=\"hidden\" type=\"hidden\" id=\"" . $this->id() . "\" name=\"$idxField\" value=\"" . $this->attrValue() . "\" />";

			$parentClass = $data->class;
			$parentField = $this->name;
						
			$iframe = "<iframe name=\"{$this->name}_iframe\" src=\"images/iframe/$parentClass/$parentID/$parentField\" style=\"height: 132px; width: 600px; border-style: none;\"></iframe>";
	
			return $iframe . $hiddenField;
		} else {
			$this->value = 'You can add images once you have saved for the first time.';
			return FormField::Field();
		}
	}

	public function saveInto($record) {
		$data = $this->form->getRecord();
		// if the record was written for the first time (has an arbitrary "new"-ID), update the imagefield to enable uploading
		if($record->ID && substr($data->ID,0,3) == 'new') {
			FormResponse::update_dom_id($this->id(), $this->Field($record->ID));
		}
	}
	
	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		$field = new SimpleImageField_Disabled($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		return $field;
	}
}
?>