<?php
	/**
	 * SubmittedFormField
	 * Data received from a UserDefinedForm submission
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