<?php

/**
 * @package cms
 */

/**
 * A file uploaded on a UserDefinedForm field
 */
class SubmittedFileField extends SubmittedFormField {
	
	static $has_one = array(
		"UploadedFile" => "File"
	);
	
}
?>