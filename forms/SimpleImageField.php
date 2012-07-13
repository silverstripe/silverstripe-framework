<?php
/**
 * SimpleImageField provides an easy way of uploading images to {@link Image} has_one relationships.
 * These relationships are auto-detected if you name the field accordingly.
 * Unlike {@link ImageField}, it doesn't use an iframe.
 * 
 * Restricts the upload size to 2MB by default, and only allows upload
 * of files with the extension 'jpg', 'gif' or 'png'.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * class Article extends DataObject {
 * 	static $has_one = array('MyImage' => 'Image');
 * }
 * // use in your form constructor etc.
 * $myField = new SimpleImageField('MyImage');
 * </code>
 * 
 * <b>Usage within a controller</b>
 * 
 * First add your $has_one relationship:
 * 
 * <code>
 * static $has_one = array(
 *    'FileName' => 'FileType'
 * );
 * </code>
 * (i.e. Image for a FileType)
 * 
 * Then add your Field into your form:
 * 
 * <code>
 * function Form() {
 *    return new Form($this, "Form", new FieldList(
 *        new SimpleImageField (
 *            $name = "FileTypeID",
 *            $title = "Upload your FileType"
 *        )
 *    ), new FieldList(
 * 
 *    // List the action buttons here - doform executes the function 'doform' below
 *        new FormAction("doform", "Submit")
 * 
 *    // List the required fields here
 *    ), new RequiredFields(
 *        "FileTypeID"
 *    ));
 * }
 * // Then make sure that the file is saved into the assets area:
 * function doform($data, $form) {
 *    $file = new File();
 *    $file->loadUploaded($_FILES['FileTypeID']);
 * 		
 *    // Redirect to a page thanking people for registering
 *    $this->redirect('thanks-for-your-submission/');
 * }
 * </code>
 * 
 * Your file should be now in the uploads directory
 * 
 * @package forms
 * @subpackage fields-files
 */

/**
 * @deprecated 3.0 Use UploadField with $myField->allowedExtensions = array('jpg', 'gif', 'png')
 */
class SimpleImageField extends FileField {

	function __construct($name, $title = null, $value = null) {
		Deprecation::notice('3.0', "SimpleImageField is deprecated. Use UploadField with \$myField->allowedExtensions = array('jpg', 'gif', 'png')", Deprecation::SCOPE_CLASS);

		if(count(func_get_args()) > 3) Deprecation::notice('3.0', 'Use setRightTitle() and setFolderName() instead of constructor arguments', Deprecation::SCOPE_GLOBAL);

		parent::__construct($name, $title, $value);

		$this->getValidator()->setAllowedExtensions(array('jpg','gif','png'));
	}

	function Field($properties = array()) {
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
	      		$html .= "<img src=\"".$imageField->Thumbnail()->getURL()."\" />";
			} else if($imageField->CMSThumbnail()) {
				$html .= "<img src=\"".$imageField->CMSThumbnail()->getURL()."\" />";
			}
			$html .= '</div>';
		}
		$html .= $this->createTag("input", 
			array(
				"type" => "file", 
				"name" => $this->name, 
				"id" => $this->id(),
				"tabindex" => $this->getAttribute('tabindex'),
				'disabled' => $this->disabled
			)
		);
		$html .= $this->createTag("input", 
			array(
				"type" => "hidden", 
				"name" => "MAX_FILE_SIZE", 
				"value" => $this->getValidator()->getAllowedMaxFileSize(),
				"tabindex" => $this->getAttribute('tabindex'),
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
	
	function Field($properties = array()) {
		$record = $this->form->getRecord();
	    $fieldName = $this->name;
			
	    $field = "<div class=\"simpleimage\">";
			if($this->value) {
				// Only the case for DataDifferencer
				$field .= $this->value;
			} else {
				if($record) $imageField = $record->$fieldName();
				if($imageField && $imageField->exists()) {
		      if($imageField->hasMethod('Thumbnail')) $field .= "<img src=\"".$imageField->Thumbnail()->URL."\" />";
		      elseif($imageField->CMSThumbnail()) $field .= "<img src=\"".$imageField->CMSThumbnail()->URL."\" />";
		      else {} // This shouldn't be called but it sometimes is for some reason, so we don't do anything
		    }else{
		    	$field .= "<label>" . _t('SimpleImageField.NOUPLOAD', 'No Image Uploaded') . "</label>";
		    }
			}
	    $field .= "</div>";
	
	    return $field;
	}

}
