<?php
/**
 * A field that will upload attached images within the CMS through an iframe.
 * If you want to upload images without iframes, see {@link SimpleImageField}.
 *
 * @uses Image_Upload
 * 
 * @package forms
 * @subpackage fields-files
 */
class ImageField extends FileField {

	public function Field($id = null) {
		$data = $this->form->getRecord();
		
		if($id && is_numeric($id)) {
			$parentID = $id;
		} elseif($data) {
			$parentID = $data->ID;
		} else {
			$parentID = null;
		}

		if($data && $parentID && is_numeric($parentID)) {
			$idxField = $this->name . 'ID';
			$hiddenField = "<input class=\"hidden\" type=\"hidden\" id=\"" .
				$this->id() . "\" name=\"$idxField\" value=\"" . $this->attrValue() . "\" />";

			$parentClass = $data->class;
			$parentField = $this->name;

			$iframe = "<iframe name=\"{$this->name}_iframe\" src=\"images/iframe/$parentClass/$parentID/$parentField\" style=\"height: 152px; width: 525px; border: none;\" frameborder=\"0\"></iframe>";

			return $iframe . $hiddenField;
		} else {
			$this->value = _t('ImageField.NOTEADDIMAGES', 'You can add images once you have saved for the first time.');
			return FormField::Field();
		}
	}


	public function saveInto($record) {
		$data = $this->form->getRecord();
		// if the record was written for the first time (has an arbitrary "new"-ID),
		// update the imagefield to enable uploading
		if($record->ID && $data && substr($data->ID, 0, 3) == 'new') {
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