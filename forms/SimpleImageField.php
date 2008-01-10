<?php

/**
 * @package forms
 * @subpackage fields-files
 */

/**
 * SimpleImageField provides an easy way of uploading images to Image has_one relationships.
 * Unlike ImageField, it doesn't use an iframe.
 * @package forms
 * @subpackage fields-files
 */
class SimpleImageField extends FileField {
  function saveInto(DataObject $record) {
    $fieldName = $this->name;
    if($record) $imageField = $record->$fieldName();
    
    
    if($imageField) $imageField->loadUploaded($this->value);
    
    $idFieldName = $fieldName . 'ID';
    if($record) $record->$idFieldName = $imageField->ID;
  }
  
  function Field() {
    $record = $this->form->getRecord();
    $fieldName = $this->name;
    if($record) {
    	$imageField = $record->$fieldName();
    } else {
    	$imageField = "";
    }
    	
    $field = "<div class=\"simpleimage\">";
    $field .= $this->createTag("input", array("type" => "file", "name" => $this->name)) . $this->createTag("input", array("type" => "hidden", "name" => "MAX_FILE_SIZE", "value" => 30*1024*1024));
    if($imageField && $imageField->exists()) {
      if($imageField->hasMethod('Thumbnail') && $imageField->Thumbnail()) $field .= "<img src=\"".$imageField->Thumbnail()->URL()."\" />";
      else if($imageField->CMSThumbnail()) $field .= "<img src=\"".$imageField->CMSThumbnail()->URL()."\" />";
      else {} // This shouldn't be called but it sometimes is for some reason, so we don't do anything
    }
    $field .= "</div>";
    
    return $field;
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

/**
 * Disabled version of {@link SimpleImageField}.
 * @package forms
 * @subpackage fields-files
 */
class SimpleImageField_Disabled extends FormField {
		
	function Field() {
		$record = $this->form->getRecord();
	    $fieldName = $this->name;
	    if($record) $imageField = $record->$fieldName();
	    
	    $field = "<div class=\"simpleimage\">";
	    if($imageField && $imageField->exists()) {
	      if($imageField->hasMethod('Thumbnail')) $field .= "<img src=\"".$imageField->Thumbnail()->URL()."\" />";
	      elseif($imageField->CMSThumbnail()) $field .= "<img src=\"".$imageField->CMSThumbnail()->URL()."\" />";
	      else {} // This shouldn't be called but it sometimes is for some reason, so we don't do anything
	    }else{
	    	$field .= "<label>" . _t('SimpleImageField.NOUPLOAD', 'No Image Uploaded') . "</label>";
	    }
	    $field .= "</div>";
	    return $field;
	}
	

}