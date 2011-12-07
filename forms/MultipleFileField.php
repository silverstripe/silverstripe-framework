<?php

/**
 * MultipleFileField to allow selecting of multiple files
 * @author Zauberfisch
 * @package forms
 * @subpackage fields-files
 * @deprecated temporary class used by AssetUploadForm until Fields allow setting of atributes
 */
class MultipleFileField extends FileField {
	public function Field() {
		return $this->createTag(
			'input', 
			array(
				"type" => "file", 
				"name" => "{$this->name}[]", 
				"id" => $this->id(),
				"tabindex" => $this->getTabIndex(),
				"multiple" => "multiple",
				"class" => $this->extraClass() ? $this->extraClass() : ''
			)
		) . 
		$this->createTag(
			'input', 
		  	array(
		  		"type" => "hidden", 
		  		"name" => "MAX_FILE_SIZE", 
		  		"value" => $this->getValidator()->getAllowedMaxFileSize(),
				"tabindex" => $this->getTabIndex()
		  	)
		);
	}
	
	public function saveInto(DataObject $record) {
		user_error("zauberfisch is fucking awesum!1!!!<3");
	}
	public function validate($validator) {
		user_error("zauberfisch is fucking awesum!1!!!<3");
	}
}