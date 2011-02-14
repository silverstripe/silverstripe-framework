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
 *    return new Form($this, "Form", new FieldSet(
 *        new SimpleImageField (
 *            $name = "FileTypeID",
 *            $title = "Upload your FileType"
 *        )
 *    ), new FieldSet(
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
 *    Director::redirect('thanks-for-your-submission/');
 * }
 * </code>
 * 
 * Your file should be now in the uploads directory
 * 
 * @package forms
 * @subpackage fields-files
 */

class SimpleImageField extends FileField {
	/**
	 * @deprecated 2.5
	 */
	public $allowedExtensions = array('jpg','gif','png');

	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null, $folderName = null) {
		parent::__construct($name, $title, $value, $form, $rightTitle, $folderName);

		$this->getValidator()->setAllowedExtensions(array('jpg','gif','png'));
	}

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
				"tabindex" => $this->getTabIndex(),
				'disabled' => $this->disabled
			)
		);
		$html .= $this->createTag("input", 
			array(
				"type" => "hidden", 
				"name" => "MAX_FILE_SIZE", 
				"value" => $this->getValidator()->getAllowedMaxFileSize(),
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
	      if($imageField->hasMethod('Thumbnail')) $field .= "<img src=\"".$imageField->Thumbnail()->URL."\" />";
	      elseif($imageField->CMSThumbnail()) $field .= "<img src=\"".$imageField->CMSThumbnail()->URL."\" />";
	      else {} // This shouldn't be called but it sometimes is for some reason, so we don't do anything
	    }else{
	    	$field .= "<label>" . _t('SimpleImageField.NOUPLOAD', 'No Image Uploaded') . "</label>";
	    }
	    $field .= "</div>";
	    return $field;
	}

}