<?php
/**
 * SimpleImageField provides an easy way of uploading images to {@link Image} has_one relationships.
 * These relationships are auto-detected if you name the field accordingly.
 * Unlike {@link ImageField}, it doesn't use an iframe.
 * 
 * Restricts the upload size to 2MB by default, and only allows upload
 * of files with the extension 'jpg', 'gif' or 'png'.
 * 
 * Example Usage:
 * <code>
 * class Article extends DataObject {
 * 	static $has_one = array('MyImage' => 'Image');
 * }
 * // use in your form constructor etc.
 * $myField = new SimpleImageField('MyImage');
 * </code>
 * 
 * @package forms
 * @subpackage fields-files
 */
class SimpleImageField extends FileField {
  
	public $allowedExtensions = array('jpg','gif','png');

	function Field() {
	    if($this->form) $record = $this->form->getRecord();
	    $fieldName = $this->name;
	    if(isset($record)&&$record) {
	    	$imageField = $record->$fieldName();
	    } else {
	    	$imageField = "";
	    }
	    	
		$html = "<div class=\"simpleimage\">";
		if($imageField && $imageField->exists()) {
			$html .= '<div class="thumbnail">';
			if($imageField->hasMethod('Thumbnail') && $imageField->Thumbnail()) {
	      		$html .= "<img src=\"".$imageField->Thumbnail()->URL()."\" />";
			} else if($imageField->CMSThumbnail()) {
				$html .= "<img src=\"".$imageField->CMSThumbnail()->URL()."\" />";
			}
			$html .= '</div>';
		}
		$html .= $this->createTag("input", 
			array(
				"type" => "file", 
				"name" => $this->name, 
				"id" => $this->id(),
				"tabindex" => $this->getTabIndex(),
				'disabled' => $this->disabled
			)
		);
		$html .= $this->createTag("input", 
			array(
				"type" => "hidden", 
				"name" => "MAX_FILE_SIZE", 
				"value" => $this->getAllowedMaxFileSize(),
				"tabindex" => $this->getTabIndex()
			)
		);
		$html .= "</div>";
		
		return $html;
	}
  
	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		$field = new SimpleImageField_Disabled($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		$field->setReadonly(true);
		return $field;
	} 
}

/**
 * Disabled version of {@link SimpleImageField}.
 * @package forms
 * @subpackage fields-files
 */
class SimpleImageField_Disabled extends FormField {
	
	protected $disabled = true;
	
	protected $readonly = true;
	
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