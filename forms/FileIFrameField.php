<?php 
/**
 * A field that will upload files to a page for use within the CMS through an iframe.
 * If you want to upload files without an iframe, use {@link FileField}.
 *
 * @used Image_Upload
 * 
 * @package forms
 * @subpackage fields-files
 */
class FileIFrameField extends FileField {
	
	public function Field() {
		$data = $this->form->getRecord();
		
		if($data && $data->ID && is_numeric($data->ID)) {
			$idxField = $this->name . 'ID';
			$hiddenField =  "<input type=\"hidden\" id=\"" . $this->id() . "\" name=\"$idxField\" value=\"" . $this->attrValue() . "\" />";
			
			$parentClass = $data->class;

			$parentID = $data->ID;
			$parentField = $this->name;
			$iframe = "<iframe name=\"{$this->name}_iframe\" src=\"images/iframe/$parentClass/$parentID/$parentField\" style=\"height: 152px; width: 600px; border-style: none;\"></iframe>";
	
			return $iframe . $hiddenField;
			
		} else {
			$this->value = _t('FileIFrameField.NOTEADDFILES', 'You can add files once you have saved for the first time.');
			return FormField::Field();
		}
	}

}
?>