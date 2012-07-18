<?php
/**
 * A field that allows you to attach an image to a record from within a iframe - designed for use in AJAX forms where it
 * is not possible to use {@link SimpleImageField}.
 * 
 * <b>Usage</b>
 * 
 * If you want to upload all assets from this field to a given folder you can define the folder in 2 ways. Either in the constructor or as a method on the field
 * 
 * <code>
 * $myField = new ImageField("myName", "Upload image below", null, null, null, "myFolder");
 * </code>
 * 
 * Will upload images into the assets/myFolder folder. If that folder does not exist it will create it for you. You can also define it as a method
 * 
 * <code>
 * $myField = new ImageField("myName");
 * $myField->setFolderName('myFolder');
 * </code>
 *
 * @deprecated 3.0 Use UploadField with $myField->allowedExtensions = array('jpg', 'gif', 'png')
 *
 * @package forms
 * @subpackage fields-files
 */
class ImageField extends FileIFrameField {
	
	/**
	 * @return SimpleImageField_Disabled
	 */
	public function performReadonlyTransformation() {
		return new SimpleImageField_Disabled($this->name, $this->title, $this->value, $this->form);
	}
	
	/**
	 * @return string
	 */
	public function FileTypeName() {
		return _t('ImageField.IMAGE', 'Image');
	}
	
	/**
	 * Adds the filter, so the dropdown displays only images and folders.
	 *
	 * @return Form
	 */
	public function EditFileForm() {
		Deprecation::notice('3.0', 'Use UploadField', Deprecation::SCOPE_CLASS);

		$filter = create_function('$item', 'return (in_array("Folder", ClassInfo::ancestry($item->ClassName)) || in_array("Image", ClassInfo::ancestry($item->ClassName)));');
		
		$form = parent::EditFileForm();
		$form->Fields()->dataFieldByName('ExistingFile')->setFilterFunction($filter);

		return $form;
	}
}
