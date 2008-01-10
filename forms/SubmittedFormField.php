<?php

/**
 * @package cms
 */

/**
 * Data received from a UserDefinedForm submission
 * @package cms
 */
class SubmittedFormField extends DataObject {
	
	static $db = array(
		"Name" => "Varchar",
		"Value" => "Text",
		"Title" => "Varchar"
	);
	
	static $has_one = array(
		"Parent" => "SubmittedForm"
	);
}
?>