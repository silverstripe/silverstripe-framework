<?php
/**
 * SubmittedForm
 * Contents of an UserDefinedForm submission
 */
class SubmittedForm extends DataObject {
	static $has_one = array(
		"SubmittedBy" => "Member",
		"Parent" => "UserDefinedForm",
	);
	
	static $db = array(
		"Recipient" => "Varchar"	
	);
	
	static $has_many = array( 
		"FieldValues" => "SubmittedFormField"
	);
	
	function SubmitTime() {
		return $this->Created;
	}
}
?>